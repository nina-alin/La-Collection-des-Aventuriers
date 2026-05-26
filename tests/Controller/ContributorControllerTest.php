<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\ContributionRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContributorControllerTest extends WebTestCase
{
    private static string $authorSlug = '';
    private static string $illustratorSlug = '';
    private static string $traductorSlug = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $editor = new Editor();
        $editor->setName('Test Editor ' . uniqid());
        $em->persist($editor);

        $book1 = new Book();
        $book1->setTitle('Author Test Book');
        $book1->setEditor($editor);
        $em->persist($book1);

        $book2 = new Book();
        $book2->setTitle('Illustrator Test Book');
        $book2->setEditor($editor);
        $em->persist($book2);

        $book3 = new Book();
        $book3->setTitle('Traductor Test Book');
        $book3->setEditor($editor);
        $em->persist($book3);

        $authorContributor = new Contributor();
        $authorContributor->setFirstName('Test');
        $authorContributor->setLastName('Author' . uniqid());
        $authorContributor->setPortraitImage('test-portrait.jpg');
        $em->persist($authorContributor);

        $illustratorContributor = new Contributor();
        $illustratorContributor->setFirstName('Test');
        $illustratorContributor->setLastName('Illustrator' . uniqid());
        $illustratorContributor->setPortraitImage('test-portrait2.jpg');
        $em->persist($illustratorContributor);

        $traductorContributor = new Contributor();
        $traductorContributor->setFirstName('Test');
        $traductorContributor->setLastName('Traductor' . uniqid());
        $em->persist($traductorContributor);

        $em->flush();

        $authorContrib = new Contribution();
        $authorContrib->setContributor($authorContributor);
        $authorContrib->setBook($book1);
        $authorContrib->setRole(ContributionRole::Author);
        $em->persist($authorContrib);

        $illustratorContrib = new Contribution();
        $illustratorContrib->setContributor($illustratorContributor);
        $illustratorContrib->setBook($book2);
        $illustratorContrib->setRole(ContributionRole::Illustrator);
        $em->persist($illustratorContrib);

        $traductorContrib = new Contribution();
        $traductorContrib->setContributor($traductorContributor);
        $traductorContrib->setBook($book3);
        $traductorContrib->setRole(ContributionRole::Traductor);
        $em->persist($traductorContrib);

        $em->flush();

        self::$authorSlug = $authorContributor->getSlug();
        self::$illustratorSlug = $illustratorContributor->getSlug();
        self::$traductorSlug = $traductorContributor->getSlug();

        self::ensureKernelShutdown();
    }

    // --- Author route tests (T019) ---

    public function testAuthorRouteReturns200ForValidSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$authorSlug);
        $this->assertResponseIsSuccessful();
    }

    public function testAuthorRouteReturns404ForUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/unknown-slug-that-does-not-exist');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAuthorRouteReturns404ForIllustratorOnlySlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$illustratorSlug);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAuthorPortraitHasNonEmptyAlt(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$authorSlug);

        $this->assertResponseIsSuccessful();
        $images = $crawler->filter('main img');
        foreach ($images as $img) {
            $this->assertNotEmpty($img->getAttribute('alt'), 'All <img> in author page must have non-empty alt');
        }
    }

    public function testSoftDeletedAuthorReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $contributor = $em->getRepository(Contributor::class)->findOneBy(['slug' => self::$authorSlug]);
        if ($contributor === null) {
            $this->markTestSkipped('Author contributor not found');
        }

        $contributor->setDeletedAt(new \DateTime());
        $em->flush();

        try {
            $client->request('GET', '/authors/' . self::$authorSlug);
            $this->assertResponseStatusCodeSame(404);
        } finally {
            $contributor->setDeletedAt(null);
            $em->flush();
        }
    }

    // --- Illustrator route tests (T022) ---

    public function testIllustratorRouteReturns200ForValidSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/illustrators/' . self::$illustratorSlug);
        $this->assertResponseIsSuccessful();
    }

    public function testIllustratorRouteReturns404ForUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/illustrators/unknown-slug-that-does-not-exist');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testIllustratorRouteReturns404ForAuthorOnlySlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/illustrators/' . self::$authorSlug);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testIllustratorCoverImagesHaveNonEmptyAlt(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/illustrators/' . self::$illustratorSlug);

        $this->assertResponseIsSuccessful();
        $images = $crawler->filter('main img');
        foreach ($images as $img) {
            $this->assertNotEmpty($img->getAttribute('alt'), 'All <img> on illustrator page must have non-empty alt');
        }
    }

    // --- Traductor route tests (T025) ---

    public function testTraductorRouteReturns200ForValidSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/traductors/' . self::$traductorSlug);
        $this->assertResponseIsSuccessful();
    }

    public function testTraductorRouteReturns404ForUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/traductors/unknown-slug-that-does-not-exist');
        $this->assertResponseStatusCodeSame(404);
    }
}
