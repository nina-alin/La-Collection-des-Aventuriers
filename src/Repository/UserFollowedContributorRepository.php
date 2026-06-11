<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use App\Entity\UserFollowedContributor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFollowedContributor>
 */
class UserFollowedContributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFollowedContributor::class);
    }

    public function isFollowing(User $user, Contributor $contributor): bool
    {
        return $this->findOneBy(['user' => $user, 'contributor' => $contributor]) !== null;
    }

    /** @return string[] UUIDs */
    public function findFollowedContributorIds(User $user): array
    {
        $rows = $this->createQueryBuilder('ufc')
            ->select('IDENTITY(ufc.contributor) AS cid')
            ->where('ufc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_map(static fn(array $r) => (string) $r['cid'], $rows));
    }

    /**
     * Returns unique recipients for a book's follow notification.
     * Each entry: ['user' => User, 'templateType' => 'contributor'|'collection', 'contributor' => ?Contributor, 'collection' => ?Collection]
     *
     * @return array<int, array{user: User, templateType: string, contributor: ?Contributor, collection: ?\App\Entity\Collection}>
     */
    public function findRecipientsForBook(Book $book): array
    {
        $em = $this->getEntityManager();

        $priorityMap = [
            ContributionRole::Author->value      => 1,
            ContributionRole::Illustrator->value => 2,
            ContributionRole::Traductor->value   => 3,
        ];

        $recipients = [];

        // Contributors of this book
        $contributions = $em->createQueryBuilder()
            ->select('contrib', 'c')
            ->from(Contribution::class, 'contrib')
            ->join('contrib.contributor', 'c')
            ->where('contrib.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getResult();

        // Users following any of the book's contributors
        foreach ($contributions as $contribution) {
            $contributor = $contribution->getContributor();
            $role        = $contribution->getRole()->value;
            $priority    = $priorityMap[$role] ?? 4;

            $followers = $this->createQueryBuilder('ufc')
                ->select('u')
                ->join('ufc.user', 'u')
                ->where('ufc.contributor = :contributor')
                ->setParameter('contributor', $contributor)
                ->getQuery()
                ->getResult();

            foreach ($followers as $user) {
                $uid = (string) $user->getId();
                if (!isset($recipients[$uid]) || $priority < $recipients[$uid]['priority']) {
                    $recipients[$uid] = [
                        'user'         => $user,
                        'templateType' => 'contributor',
                        'contributor'  => $contributor,
                        'collection'   => null,
                        'priority'     => $priority,
                    ];
                }
            }
        }

        // Users following the book's collection (only if not already added via contributor with higher priority)
        $collection = $book->getCollection();
        if ($collection !== null) {
            $collectionFollowers = $em->createQueryBuilder()
                ->select('u')
                ->from(UserCollectionSubscription::class, 'ucs')
                ->join('ucs.user', 'u')
                ->where('ucs.collection = :collection')
                ->setParameter('collection', $collection)
                ->getQuery()
                ->getResult();

            foreach ($collectionFollowers as $user) {
                $uid = (string) $user->getId();
                if (!isset($recipients[$uid])) {
                    $recipients[$uid] = [
                        'user'         => $user,
                        'templateType' => 'collection',
                        'contributor'  => null,
                        'collection'   => $collection,
                        'priority'     => 4,
                    ];
                }
            }
        }

        return array_values(array_map(static function (array $r): array {
            unset($r['priority']);
            return $r;
        }, $recipients));
    }
}
