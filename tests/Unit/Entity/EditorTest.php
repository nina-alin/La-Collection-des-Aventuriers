<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Editor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EditorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
        parent::tearDown();
    }

    public function testFieldMappingRoundTrip(): void
    {
        $editor = new Editor();
        $editor->setName('Gallimard');

        $this->em->persist($editor);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(Editor::class)->find($editor->getId());

        $this->assertNotNull($found);
        $this->assertSame('Gallimard', $found->getName());
        $this->assertNotEmpty($found->getSlug());
    }

    public function testSlugIsGeneratedFromName(): void
    {
        $editor = new Editor();
        $editor->setName('Editions du Seuil');

        $this->em->persist($editor);
        $this->em->flush();

        $this->assertNotEmpty($editor->getSlug());
    }

    public function testSlugUniqueConstraintEnforced(): void
    {
        $e1 = (new Editor())->setName('Hachette');
        $e2 = (new Editor())->setName('Hachette');

        $this->em->persist($e1);
        $this->em->flush();
        $this->em->persist($e2);
        $this->em->flush();

        $this->assertNotSame($e1->getSlug(), $e2->getSlug());
    }
}
