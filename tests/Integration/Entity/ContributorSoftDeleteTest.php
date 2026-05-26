<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\ContributionRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContributorSoftDeleteTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testSoftDeleteContributorDoesNotCascadeToContributions(): void
    {
        $editor = new Editor();
        $editor->setName('Test Editor');
        $this->em->persist($editor);

        $book = new Book();
        $book->setTitle('Test Book for Soft Delete');
        $book->setEditor($editor);
        $this->em->persist($book);

        $contributor = new Contributor();
        $contributor->setFirstName('Test');
        $contributor->setLastName('Author');
        $this->em->persist($contributor);

        $this->em->flush();

        $contribution = new Contribution();
        $contribution->setContributor($contributor);
        $contribution->setBook($book);
        $contribution->setRole(ContributionRole::Author);
        $this->em->persist($contribution);
        $this->em->flush();

        $contributionId = $contribution->getId();

        $contributor->setDeletedAt(new \DateTime());
        $this->em->flush();

        $this->em->clear();

        $reloadedContribution = $this->em->find(Contribution::class, $contributionId);
        $this->assertNotNull($reloadedContribution, 'Contribution row must still exist after Contributor soft-delete');
        $this->assertNull($reloadedContribution->getDeletedAt(), 'Contribution deletedAt must remain null when only Contributor is soft-deleted');
    }
}
