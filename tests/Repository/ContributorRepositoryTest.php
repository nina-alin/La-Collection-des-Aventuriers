<?php

namespace App\Tests\Repository;

use App\Dto\ContributorFilterState;
use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\ContributionRole;
use App\Entity\User;
use App\Entity\UserFollowedContributor;
use App\Repository\ContributorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContributorRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ContributorRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em   = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo = static::getContainer()->get(ContributorRepository::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Contribution ct WHERE ct.contributor IN (SELECT cc.id FROM App\Entity\Contributor cc WHERE cc.slug LIKE :prefix)')
            ->setParameter('prefix', '__test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.title LIKE :prefix')
            ->setParameter('prefix', '__test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Contributor c WHERE c.slug LIKE :prefix')
            ->setParameter('prefix', '__test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', '__test_%')
            ->execute();
        parent::tearDown();
    }

    private function makeEditor(): Editor
    {
        $e = new Editor();
        $e->setName('__test_editor_' . uniqid());
        $this->em->persist($e);
        return $e;
    }

    private function makeBook(Editor $editor, string $suffix = ''): Book
    {
        $uid = uniqid();
        $b   = new Book();
        $b->setTitle('__test_book_' . $suffix . $uid);
        $b->setStatus(BookStatus::PUBLISHED);
        $b->setEditor($editor);
        $this->em->persist($b);
        return $b;
    }

    private function makeContributor(string $firstName, string $lastName): Contributor
    {
        $uid = uniqid();
        $c   = new Contributor();
        $c->setFirstName($firstName);
        $c->setLastName($lastName);
        $c->setSlug('__test-' . mb_strtolower($lastName) . '-' . $uid);
        $this->em->persist($c);
        return $c;
    }

    private function makeContribution(Contributor $contributor, Book $book, ContributionRole $role): Contribution
    {
        $ct = new Contribution();
        $ct->setContributor($contributor);
        $ct->setBook($book);
        $ct->setRole($role);
        $this->em->persist($ct);
        return $ct;
    }

    public function testFindPaginatedFilteredReturnsPaginator(): void
    {
        $editor = $this->makeEditor();
        $book   = $this->makeBook($editor);
        $author = $this->makeContributor('Test', 'Author');
        $this->makeContribution($author, $book, ContributionRole::Author);
        $this->em->flush();

        $state  = new ContributorFilterState();
        $result = $this->repo->findPaginatedFiltered($state);

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testFindPaginatedFilteredWithRoleFilter(): void
    {
        $editor       = $this->makeEditor();
        $book         = $this->makeBook($editor);
        $author       = $this->makeContributor('Role', 'Author');
        $illustrateur = $this->makeContributor('Role', 'Illustrateur');
        $this->makeContribution($author, $book, ContributionRole::Author);
        $book2 = $this->makeBook($editor, 'illus');
        $this->makeContribution($illustrateur, $book2, ContributionRole::Illustrator);
        $this->em->flush();

        $state  = new ContributorFilterState(role: 'auteur');
        $result = $this->repo->findPaginatedFiltered($state);
        $ids    = [];
        foreach ($result as $c) {
            $ids[] = $c->getSlug();
        }

        $this->assertContains($author->getSlug(), $ids);
        $this->assertNotContains($illustrateur->getSlug(), $ids);
    }

    public function testFindPaginatedFilteredWithLetterFilter(): void
    {
        $editor = $this->makeEditor();
        $book   = $this->makeBook($editor);
        $alpha  = $this->makeContributor('Alpha', 'Zebra');
        $this->makeContribution($alpha, $book, ContributionRole::Author);
        $this->em->flush();

        $state  = new ContributorFilterState(letter: 'Z');
        $result = $this->repo->findPaginatedFiltered($state);
        $ids    = [];
        foreach ($result as $c) {
            $ids[] = $c->getSlug();
        }

        $this->assertContains($alpha->getSlug(), $ids);
    }

    public function testFindAvailableLettersReturnsDistinctSorted(): void
    {
        $editor = $this->makeEditor();
        $bookA  = $this->makeBook($editor, 'a');
        $bookZ  = $this->makeBook($editor, 'z');
        $cA     = $this->makeContributor('Aaa', 'Aardvark');
        $cZ     = $this->makeContributor('Zzz', 'Zulu');
        $this->makeContribution($cA, $bookA, ContributionRole::Author);
        $this->makeContribution($cZ, $bookZ, ContributionRole::Author);
        $this->em->flush();

        $letters = $this->repo->findAvailableLetters(new ContributorFilterState());

        $this->assertContains('A', $letters);
        $this->assertContains('Z', $letters);
        $this->assertSame(array_unique($letters), $letters);
        $sorted = $letters;
        sort($sorted);
        $this->assertSame($sorted, $letters);
    }

    public function testFindAvailableLettersRespectRoleFilter(): void
    {
        $editor = $this->makeEditor();
        $bookA  = $this->makeBook($editor, 'ra');
        $bookB  = $this->makeBook($editor, 'rb');
        $cA     = $this->makeContributor('Only', 'Author');
        $cT     = $this->makeContributor('Only', 'Traducteur');
        $this->makeContribution($cA, $bookA, ContributionRole::Author);
        $this->makeContribution($cT, $bookB, ContributionRole::Traductor);
        $this->em->flush();

        $auteurLetters = $this->repo->findAvailableLetters(new ContributorFilterState(role: 'auteur'));
        $tradLetters   = $this->repo->findAvailableLetters(new ContributorFilterState(role: 'traducteur'));

        $this->assertContains('A', $auteurLetters);
        $this->assertContains('T', $tradLetters);
    }

    public function testFindCardDataBatchReturnsBookCountAndRoles(): void
    {
        $editor = $this->makeEditor();
        $book1  = $this->makeBook($editor, 'cd1');
        $book2  = $this->makeBook($editor, 'cd2');
        $c      = $this->makeContributor('Card', 'DataTest');
        $this->makeContribution($c, $book1, ContributionRole::Author);
        $this->makeContribution($c, $book2, ContributionRole::Author);
        $this->em->flush();

        $id     = $c->getId()->toRfc4122();
        $result = $this->repo->findCardDataBatch([$id]);

        $this->assertArrayHasKey($id, $result);
        $this->assertSame(2, $result[$id]['bookCount']);
        $this->assertContains(ContributionRole::Author->value, $result[$id]['roles']);
    }

    public function testFindCardDataBatchReturnsEmptyForNoIds(): void
    {
        $this->assertSame([], $this->repo->findCardDataBatch([]));
    }

    public function testCountFilteredBasic(): void
    {
        $editor = $this->makeEditor();
        $book   = $this->makeBook($editor, 'count');
        $c1     = $this->makeContributor('Count', 'One');
        $c2     = $this->makeContributor('Count', 'Two');
        $this->makeContribution($c1, $book, ContributionRole::Author);
        $this->makeContribution($c2, $book, ContributionRole::Illustrator);
        $this->em->flush();

        $total = $this->repo->countFiltered(new ContributorFilterState());
        $this->assertGreaterThanOrEqual(2, $total);
    }

    public function testFindRoleCountsReturnsAllRoleKeys(): void
    {
        $counts = $this->repo->findRoleCounts();

        $this->assertArrayHasKey('auteur', $counts);
        $this->assertArrayHasKey('illustrateur', $counts);
        $this->assertArrayHasKey('traducteur', $counts);
        $this->assertArrayHasKey('tous', $counts);
        $this->assertGreaterThanOrEqual($counts['auteur'], $counts['tous']);
    }

    public function testOnlyFollowedFilterWithNoFollowsReturnsEmpty(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            $this->markTestSkipped('No user in DB.');
        }

        // Remove any existing follows for this user
        $follows = $this->em->getRepository(UserFollowedContributor::class)->findBy(['user' => $user]);
        foreach ($follows as $follow) {
            $this->em->remove($follow);
        }
        $this->em->flush();

        $state     = new ContributorFilterState(onlyFollowed: true);
        $paginator = $this->repo->findPaginatedFiltered($state, $user);

        $this->assertCount(0, $paginator);
    }

    public function testOnlyFollowedFilterReturnsOnlyFollowedContributor(): void
    {
        $editor      = $this->makeEditor();
        $book        = $this->makeBook($editor, 'followed');
        $contributor = $this->makeContributor('Follow', 'TestUser');
        $this->makeContribution($contributor, $book, ContributionRole::Author);
        $this->em->flush();

        $user = $this->em->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            $this->markTestSkipped('No user in DB.');
        }

        // Remove any existing follows for this user
        $follows = $this->em->getRepository(UserFollowedContributor::class)->findBy(['user' => $user]);
        foreach ($follows as $follow) {
            $this->em->remove($follow);
        }

        $follow = new UserFollowedContributor($user, $contributor);
        $this->em->persist($follow);
        $this->em->flush();

        $state     = new ContributorFilterState(onlyFollowed: true);
        $paginator = $this->repo->findPaginatedFiltered($state, $user);

        $ids = [];
        foreach ($paginator as $c) {
            $ids[] = (string) $c->getId();
        }

        $this->assertContains((string) $contributor->getId(), $ids);

        $this->em->remove($follow);
        $this->em->flush();
    }

    public function testOnlyFollowedFalseWithNullUserReturnsAllContributors(): void
    {
        $state     = new ContributorFilterState(onlyFollowed: false);
        $paginator = $this->repo->findPaginatedFiltered($state, null);

        $this->assertInstanceOf(Paginator::class, $paginator);
    }
}
