<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Illustrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IllustratorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Illustrator i')->execute();
        parent::tearDown();
    }

    public function testFieldMappingRoundTrip(): void
    {
        $illustrator = new Illustrator();
        $illustrator->setFirstName('Claude');
        $illustrator->setLastName('Monet');

        $this->em->persist($illustrator);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(Illustrator::class)->find($illustrator->getId());

        $this->assertNotNull($found);
        $this->assertSame('Claude', $found->getFirstName());
        $this->assertSame('Monet', $found->getLastName());
        $this->assertNotEmpty($found->getSlug());
    }

    public function testSlugIsGeneratedFromName(): void
    {
        $illustrator = new Illustrator();
        $illustrator->setFirstName('Pablo');
        $illustrator->setLastName('Picasso');

        $this->em->persist($illustrator);
        $this->em->flush();

        $this->assertStringContainsString('pablo', $illustrator->getSlug());
    }

    public function testSlugUniqueConstraintEnforced(): void
    {
        $i1 = (new Illustrator())->setFirstName('Luc')->setLastName('Bernard');
        $i2 = (new Illustrator())->setFirstName('Luc')->setLastName('Bernard');

        $this->em->persist($i1);
        $this->em->flush();
        $this->em->persist($i2);
        $this->em->flush();

        $this->assertNotSame($i1->getSlug(), $i2->getSlug());
    }
}
