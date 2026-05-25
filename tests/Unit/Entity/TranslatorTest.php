<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Translator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TranslatorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Translator t')->execute();
        parent::tearDown();
    }

    public function testFieldMappingRoundTrip(): void
    {
        $translator = new Translator();
        $translator->setFirstName('Antoine');
        $translator->setLastName('Berman');

        $this->em->persist($translator);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(Translator::class)->find($translator->getId());

        $this->assertNotNull($found);
        $this->assertSame('Antoine', $found->getFirstName());
        $this->assertSame('Berman', $found->getLastName());
        $this->assertNotEmpty($found->getSlug());
    }

    public function testSlugIsGeneratedFromName(): void
    {
        $translator = new Translator();
        $translator->setFirstName('Henri');
        $translator->setLastName('Meschonnic');

        $this->em->persist($translator);
        $this->em->flush();

        $this->assertStringContainsString('henri', $translator->getSlug());
    }

    public function testSlugUniqueConstraintEnforced(): void
    {
        $t1 = (new Translator())->setFirstName('Pierre')->setLastName('Durand');
        $t2 = (new Translator())->setFirstName('Pierre')->setLastName('Durand');

        $this->em->persist($t1);
        $this->em->flush();
        $this->em->persist($t2);
        $this->em->flush();

        $this->assertNotSame($t1->getSlug(), $t2->getSlug());
    }
}
