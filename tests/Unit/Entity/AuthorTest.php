<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AuthorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Author a')->execute();
        parent::tearDown();
    }

    public function testFieldMappingRoundTrip(): void
    {
        $author = new Author();
        $author->setFirstName('Jean');
        $author->setLastName('Dupont');

        $this->em->persist($author);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(Author::class)->find($author->getId());

        $this->assertNotNull($found);
        $this->assertSame('Jean', $found->getFirstName());
        $this->assertSame('Dupont', $found->getLastName());
        $this->assertNotEmpty($found->getSlug());
    }

    public function testSlugIsGeneratedFromName(): void
    {
        $author = new Author();
        $author->setFirstName('Marie');
        $author->setLastName('Curie');

        $this->em->persist($author);
        $this->em->flush();

        $this->assertStringContainsString('marie', $author->getSlug());
        $this->assertStringContainsString('curie', $author->getSlug());
    }

    public function testSlugUniqueConstraintEnforced(): void
    {
        $a1 = (new Author())->setFirstName('Paul')->setLastName('Martin');
        $a2 = (new Author())->setFirstName('Paul')->setLastName('Martin');

        $this->em->persist($a1);
        $this->em->flush();
        $this->em->persist($a2);
        $this->em->flush();

        $this->assertNotSame($a1->getSlug(), $a2->getSlug());
    }
}
