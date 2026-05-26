<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

class ContributionTest extends TestCase
{
    public function testUuidGeneratedInConstructor(): void
    {
        $contribution = new Contribution();
        $this->assertNotNull($contribution->getId());
        $this->assertInstanceOf(Uuid::class, $contribution->getId());
    }

    public function testFieldGettersSetters(): void
    {
        $contribution = new Contribution();

        $contributor = new Contributor();
        $contribution->setContributor($contributor);
        $this->assertSame($contributor, $contribution->getContributor());

        $book = new Book();
        $contribution->setBook($book);
        $this->assertSame($book, $contribution->getBook());

        foreach (ContributionRole::cases() as $role) {
            $contribution->setRole($role);
            $this->assertSame($role, $contribution->getRole());
        }

        $contribution->setDetails('Some details');
        $this->assertSame('Some details', $contribution->getDetails());

        $contribution->setDetails(null);
        $this->assertNull($contribution->getDetails());

        $deletedAt = new \DateTime();
        $contribution->setDeletedAt($deletedAt);
        $this->assertSame($deletedAt, $contribution->getDeletedAt());
    }

    public function testDeletedAtNullableAndNullByDefault(): void
    {
        $contribution = new Contribution();
        $this->assertNull($contribution->getDeletedAt());
    }

    public function testUniqueConstraintAnnotationPresent(): void
    {
        $ref = new \ReflectionClass(Contribution::class);
        $ormConstraints = $ref->getAttributes(ORM\UniqueConstraint::class);
        $this->assertNotEmpty($ormConstraints, 'Contribution must have #[ORM\UniqueConstraint]');
    }

    public function testUniqueEntityAnnotationPresent(): void
    {
        $ref = new \ReflectionClass(Contribution::class);
        $attributes = $ref->getAttributes(UniqueEntity::class);
        $this->assertNotEmpty($attributes, 'Contribution must have #[UniqueEntity]');
    }

    public function testSoftDeleteableAnnotationPresent(): void
    {
        $ref = new \ReflectionClass(Contribution::class);
        $attributes = $ref->getAttributes(Gedmo\SoftDeleteable::class);
        $this->assertNotEmpty($attributes, 'Contribution must have #[Gedmo\SoftDeleteable]');
    }
}
