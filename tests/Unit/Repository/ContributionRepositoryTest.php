<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\ContributionRole;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use App\Repository\ContributionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContributionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ContributionRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(Contribution::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Contribution co WHERE co.book IN (SELECT b FROM App\Entity\Book b WHERE b.collection IS NOT NULL)')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.collection IS NOT NULL')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Collection c WHERE c.nom LIKE :prefix')
            ->setParameter('prefix', 'ContribTest%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Contributor c WHERE c.firstName LIKE :prefix')
            ->setParameter('prefix', 'ContribTest%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', 'ContribTest%')
            ->execute();
        parent::tearDown();
    }

    private function makeCollection(): Collection
    {
        $c = new Collection();
        $c->setNom('ContribTest Collection ' . uniqid());
        $c->setDescription('Test');
        $c->setGenre(GenreCollection::AVENTURE);
        $c->setStatut(StatutCollection::EN_COURS);
        $this->em->persist($c);
        return $c;
    }

    private function makeEditor(): Editor
    {
        $e = new Editor();
        $e->setName('ContribTest Editor ' . uniqid());
        $this->em->persist($e);
        return $e;
    }

    private function makeBook(Collection $collection, Editor $editor): Book
    {
        $b = new Book();
        $b->setTitle('Book ' . uniqid());
        $b->setStatus(BookStatus::PUBLISHED);
        $b->setCollection($collection);
        $b->setEditor($editor);
        $this->em->persist($b);
        return $b;
    }

    private function makeContributor(string $firstName, string $lastName): Contributor
    {
        $c = new Contributor();
        $c->setFirstName('ContribTest' . $firstName);
        $c->setLastName($lastName);
        $c->setSlug('contribtest-' . strtolower($firstName) . '-' . uniqid());
        $this->em->persist($c);
        return $c;
    }

    private function makeContribution(Contributor $contributor, Book $book, ContributionRole $role): Contribution
    {
        $c = new Contribution();
        $c->setContributor($contributor);
        $c->setBook($book);
        $c->setRole($role);
        $this->em->persist($c);
        return $c;
    }

    public function testGroupsByContributorAndRole(): void
    {
        $collection = $this->makeCollection();
        $editor = $this->makeEditor();
        $book1 = $this->makeBook($collection, $editor);
        $book2 = $this->makeBook($collection, $editor);
        $contributor = $this->makeContributor('Joe', 'Dever');
        $this->makeContribution($contributor, $book1, ContributionRole::Author);
        $this->makeContribution($contributor, $book2, ContributionRole::Author);
        $this->em->flush();

        $results = $this->repo->findRecurringByCollection($collection);

        $this->assertCount(1, $results);
        $this->assertSame(ContributionRole::Author, $results[0]['role']);
        $this->assertSame(2, $results[0]['count']);
        $this->assertSame($contributor->getId()->toRfc4122(), $results[0]['contributor']->getId()->toRfc4122());
    }

    public function testMultipleRolesCreateSeparateRows(): void
    {
        $collection = $this->makeCollection();
        $editor = $this->makeEditor();
        $book1 = $this->makeBook($collection, $editor);
        $book2 = $this->makeBook($collection, $editor);
        $contributor = $this->makeContributor('Brian', 'Williams');
        $this->makeContribution($contributor, $book1, ContributionRole::Author);
        $this->makeContribution($contributor, $book2, ContributionRole::Illustrator);
        $this->em->flush();

        $results = $this->repo->findRecurringByCollection($collection);

        $this->assertCount(2, $results);
        $roles = array_map(fn ($r) => $r['role'], $results);
        $this->assertContains(ContributionRole::Author, $roles);
        $this->assertContains(ContributionRole::Illustrator, $roles);
    }

    public function testOrderedByCountDesc(): void
    {
        $collection = $this->makeCollection();
        $editor = $this->makeEditor();
        $book1 = $this->makeBook($collection, $editor);
        $book2 = $this->makeBook($collection, $editor);
        $book3 = $this->makeBook($collection, $editor);

        $rareContributor = $this->makeContributor('Rare', 'Person');
        $frequentContributor = $this->makeContributor('Frequent', 'Person');

        $this->makeContribution($rareContributor, $book1, ContributionRole::Illustrator);
        $this->makeContribution($frequentContributor, $book1, ContributionRole::Author);
        $this->makeContribution($frequentContributor, $book2, ContributionRole::Author);
        $this->makeContribution($frequentContributor, $book3, ContributionRole::Author);
        $this->em->flush();

        $results = $this->repo->findRecurringByCollection($collection);

        $this->assertSame(3, $results[0]['count']);
        $this->assertSame(1, $results[1]['count']);
    }

    public function testRespectsSoftDeleteOnContribution(): void
    {
        $collection = $this->makeCollection();
        $editor = $this->makeEditor();
        $book1 = $this->makeBook($collection, $editor);
        $book2 = $this->makeBook($collection, $editor);
        $contributor = $this->makeContributor('Deleted', 'Author');

        $contrib1 = $this->makeContribution($contributor, $book1, ContributionRole::Author);
        $contrib2 = $this->makeContribution($contributor, $book2, ContributionRole::Author);
        $this->em->flush();

        $contrib2->setDeletedAt(new \DateTime());
        $this->em->flush();

        $results = $this->repo->findRecurringByCollection($collection);

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]['count']);
    }

    public function testEmptyResultForCollectionWithoutContributions(): void
    {
        $collection = $this->makeCollection();
        $this->em->flush();

        $results = $this->repo->findRecurringByCollection($collection);

        $this->assertSame([], $results);
    }
}
