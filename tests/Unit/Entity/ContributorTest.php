<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Contributor;
use Gedmo\Mapping\Annotation as Gedmo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ContributorTest extends TestCase
{
    public function testUuidGeneratedInConstructor(): void
    {
        $contributor = new Contributor();
        $this->assertNotNull($contributor->getId());
        $this->assertInstanceOf(Uuid::class, $contributor->getId());
    }

    public function testFieldGettersSetters(): void
    {
        $contributor = new Contributor();

        $contributor->setFirstName('Steve');
        $this->assertSame('Steve', $contributor->getFirstName());

        $contributor->setLastName('Jackson');
        $this->assertSame('Jackson', $contributor->getLastName());

        $contributor->setPseudo('SJ');
        $this->assertSame('SJ', $contributor->getPseudo());

        $contributor->setSlug('steve-jackson');
        $this->assertSame('steve-jackson', $contributor->getSlug());

        $contributor->setBiography('A British author.');
        $this->assertSame('A British author.', $contributor->getBiography());

        $contributor->setNationality('GB');
        $this->assertSame('GB', $contributor->getNationality());

        $birthDate = new \DateTime('1951-01-01');
        $contributor->setBirthDate($birthDate);
        $this->assertSame($birthDate, $contributor->getBirthDate());

        $deathDate = new \DateTime('2020-12-31');
        $contributor->setDeathDate($deathDate);
        $this->assertSame($deathDate, $contributor->getDeathDate());

        $contributor->setPortraitImage('portrait.jpg');
        $this->assertSame('portrait.jpg', $contributor->getPortraitImage());

        $deletedAt = new \DateTime();
        $contributor->setDeletedAt($deletedAt);
        $this->assertSame($deletedAt, $contributor->getDeletedAt());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $contributor = new Contributor();
        $this->assertNull($contributor->getPseudo());
        $this->assertNull($contributor->getBiography());
        $this->assertNull($contributor->getNationality());
        $this->assertNull($contributor->getBirthDate());
        $this->assertNull($contributor->getDeathDate());
        $this->assertNull($contributor->getPortraitImage());
        $this->assertNull($contributor->getDeletedAt());
    }

    public function testSoftDeleteableAnnotationPresent(): void
    {
        $ref = new \ReflectionClass(Contributor::class);
        $attributes = $ref->getAttributes(Gedmo\SoftDeleteable::class);
        $this->assertNotEmpty($attributes, 'Contributor must have #[Gedmo\SoftDeleteable]');
    }

    public function testContributionsCollectionInitialized(): void
    {
        $contributor = new Contributor();
        $this->assertCount(0, $contributor->getContributions());
    }
}
