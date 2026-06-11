<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\User;
use App\Entity\UserCollectionSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollectionSubscription>
 */
class UserCollectionSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCollectionSubscription::class);
    }

    /** @return User[] */
    public function findSubscribersByCollection(Collection $collection): array
    {
        return $this->createQueryBuilder('s')
            ->select('u')
            ->join('s.user', 'u')
            ->where('s.collection = :collection')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getResult();
    }

    /** @return string[] UUIDs */
    public function findFollowedCollectionIds(User $user): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.collection) AS cid')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_map(static fn(array $r) => (string) $r['cid'], $rows));
    }

    /** @return UserCollectionSubscription[] */
    public function findFollowedByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function findByUserAndCollection(User $user, Collection $collection): ?UserCollectionSubscription
    {
        return $this->findOneBy(['user' => $user, 'collection' => $collection]);
    }
}
