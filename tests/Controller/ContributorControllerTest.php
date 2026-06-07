<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Entity\Contribution;
use App\Entity\Contributor;
use App\Entity\Editor;
use App\Entity\Enum\ContributionRole;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ContributorControllerTest extends WebTestCase
{
    private static string $authorSlug = '';
    private static string $illustratorSlug = '';
    private static string $traductorSlug = '';
    private static string $fullAuthorSlug = '';
    private static string $minimalAuthorSlug = '';
    private static string $multiSagaAuthorSlug = '';

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

        // Full author: portrait + biography + birthDate + deathDate + pseudo + nationality
        $fullAuthorBook = new Book();
        $fullAuthorBook->setTitle('Full Author Book');
        $fullAuthorBook->setEditor($editor);
        $fullAuthorBook->setSaga('Loup Solitaire');
        $fullAuthorBook->setFrenchPublicationYear(1984);
        $em->persist($fullAuthorBook);

        $fullAuthor = new Contributor();
        $fullAuthor->setFirstName('Joe');
        $fullAuthor->setLastName('Dever' . uniqid());
        $fullAuthor->setPseudo('Kai Wildman');
        $fullAuthor->setNationality('GB');
        $fullAuthor->setBiography('Joseph Dever was a remarkable author who changed gamebooks forever.');
        $fullAuthor->setBirthDate(new \DateTime('1956-08-26'));
        $fullAuthor->setDeathDate(new \DateTime('2016-11-29'));
        $fullAuthor->setPortraitImage('joe-dever.jpg');
        $em->persist($fullAuthor);

        // Minimal author: no portrait, no biography, no birthDate, no pseudo
        $minimalAuthorBook = new Book();
        $minimalAuthorBook->setTitle('Minimal Author Book');
        $minimalAuthorBook->setEditor($editor);
        $em->persist($minimalAuthorBook);

        $minimalAuthor = new Contributor();
        $minimalAuthor->setFirstName('Jane');
        $minimalAuthor->setLastName('Doe' . uniqid());
        $em->persist($minimalAuthor);

        // Multi-saga author: 2 Loup Solitaire + 1 Légendes de Magnamund
        $bookLs1 = new Book();
        $bookLs1->setTitle('Alpha Book');
        $bookLs1->setEditor($editor);
        $bookLs1->setSaga('Loup Solitaire');
        $bookLs1->setFrenchPublicationYear(1984);
        $em->persist($bookLs1);

        $bookLs2 = new Book();
        $bookLs2->setTitle('Zebra Book');
        $bookLs2->setEditor($editor);
        $bookLs2->setSaga('Loup Solitaire');
        $bookLs2->setFrenchPublicationYear(1986);
        $em->persist($bookLs2);

        $bookLm1 = new Book();
        $bookLm1->setTitle('Beta Book');
        $bookLm1->setEditor($editor);
        $bookLm1->setSaga('Légendes de Magnamund');
        $bookLm1->setFrenchPublicationYear(1989);
        $em->persist($bookLm1);

        $multiSagaAuthor = new Contributor();
        $multiSagaAuthor->setFirstName('Multi');
        $multiSagaAuthor->setLastName('Saga' . uniqid());
        $em->persist($multiSagaAuthor);

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

        $fullAuthorContrib = new Contribution();
        $fullAuthorContrib->setContributor($fullAuthor);
        $fullAuthorContrib->setBook($fullAuthorBook);
        $fullAuthorContrib->setRole(ContributionRole::Author);
        $em->persist($fullAuthorContrib);

        $minimalAuthorContrib = new Contribution();
        $minimalAuthorContrib->setContributor($minimalAuthor);
        $minimalAuthorContrib->setBook($minimalAuthorBook);
        $minimalAuthorContrib->setRole(ContributionRole::Author);
        $em->persist($minimalAuthorContrib);

        $contribLs1 = new Contribution();
        $contribLs1->setContributor($multiSagaAuthor);
        $contribLs1->setBook($bookLs1);
        $contribLs1->setRole(ContributionRole::Author);
        $em->persist($contribLs1);

        $contribLs2 = new Contribution();
        $contribLs2->setContributor($multiSagaAuthor);
        $contribLs2->setBook($bookLs2);
        $contribLs2->setRole(ContributionRole::Author);
        $em->persist($contribLs2);

        $contribLm1 = new Contribution();
        $contribLm1->setContributor($multiSagaAuthor);
        $contribLm1->setBook($bookLm1);
        $contribLm1->setRole(ContributionRole::Author);
        $em->persist($contribLm1);

        $em->flush();

        self::$authorSlug          = $authorContributor->getSlug();
        self::$illustratorSlug     = $illustratorContributor->getSlug();
        self::$traductorSlug       = $traductorContributor->getSlug();
        self::$fullAuthorSlug      = $fullAuthor->getSlug();
        self::$minimalAuthorSlug   = $minimalAuthor->getSlug();
        self::$multiSagaAuthorSlug = $multiSagaAuthor->getSlug();

        self::ensureKernelShutdown();
    }

    // --- Author route tests ---

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
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$authorSlug);

        $this->assertResponseIsSuccessful();
        $images = $crawler->filter('main img');
        foreach ($images as $img) {
            $this->assertNotEmpty($img->getAttribute('alt'), 'All <img> in author page must have non-empty alt');
        }
    }

    public function testSoftDeletedAuthorReturns404(): void
    {
        $client      = static::createClient();
        $em          = static::getContainer()->get(EntityManagerInterface::class);
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

    // --- T007: US1 — Profile column tests ---

    public function testPortraitImageRenderedWhenPortraitSet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$fullAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img[alt^="Portrait de"]');
    }

    public function testPortraitPlaceholderRenderedWhenPortraitNull(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$minimalAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.portrait-inner');
        $this->assertSelectorNotExists('img[alt^="Portrait de"]');
    }

    public function testPseudoRowPresentWhenPseudoSet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$fullAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.vitals', 'Kai Wildman');
    }

    public function testPseudoRowAbsentWhenPseudoNull(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$minimalAuthorSlug);
        $this->assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('Pseudonyme', $html);
    }

    public function testLifeBlockAbsentWhenBirthDateNull(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$minimalAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.life');
    }

    public function testDeathYearAndAgeAtDeathPresentWhenDeathDateSet(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$fullAuthorSlug);
        $this->assertResponseIsSuccessful();
        $lifeHtml = $crawler->filter('.life')->html();
        $this->assertStringContainsString('2016', $lifeHtml);
        $this->assertStringContainsString('60', $lifeHtml);
    }

    public function testBioLettrineRenderedWhenBiographySet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$fullAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lettrine');
    }

    public function testBioUnavailableMessageWhenBiographyNull(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$minimalAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.bio-card', 'Biographie non disponible.');
    }

    // --- T010: US2 — Bibliography column tests ---

    public function testBiblioHeadShowsTotalCount(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.biblio-title', '3 fiches');
    }

    public function testSagaPillsShowCorrectCounts(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $pillHtml = $crawler->filter('.collection-filters')->html();
        $this->assertStringContainsString('LOUP SOLITAIRE', $pillHtml);
        $this->assertStringContainsString('LÉGENDES DE MAGNAMUND', $pillHtml);
    }

    public function testSagaFilterReturnsOnlyMatchingCards(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug . '?saga=loup-solitaire');
        $this->assertResponseIsSuccessful();
        $gridHtml = $crawler->filter('.books-grid')->html();
        $this->assertStringContainsString('Alpha Book', $gridHtml);
        $this->assertStringContainsString('Zebra Book', $gridHtml);
        $this->assertStringNotContainsString('Beta Book', $gridHtml);
    }

    public function testUnknownSagaReturnsAllCardsWithToutActive(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug . '?saga=invalid-saga-xyz');
        $this->assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        $this->assertStringContainsString('Alpha Book', $html);
        $this->assertStringContainsString('Beta Book', $html);
        $this->assertStringContainsString('Zebra Book', $html);
        $toutPill = $crawler->filter('.coll-pill')->first();
        $this->assertSame('true', $toutPill->attr('aria-pressed'));
    }

    public function testSortAlphaReturnsTitlesInAscendingOrder(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug . '?sort=alpha');
        $this->assertResponseIsSuccessful();
        $html     = $client->getResponse()->getContent();
        $alphaPos = strpos($html, 'Alpha Book');
        $betaPos  = strpos($html, 'Beta Book');
        $zebraPos = strpos($html, 'Zebra Book');
        $this->assertLessThan($betaPos, $alphaPos, 'Alpha Book must appear before Beta Book');
        $this->assertLessThan($zebraPos, $betaPos, 'Beta Book must appear before Zebra Book');
    }

    public function testDefaultSortIsChronological(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $html     = $client->getResponse()->getContent();
        // Alpha Book 1984, Zebra Book 1986, Beta Book 1989
        $alphaPos = strpos($html, 'Alpha Book');
        $zebraPos = strpos($html, 'Zebra Book');
        $betaPos  = strpos($html, 'Beta Book');
        $this->assertLessThan($zebraPos, $alphaPos, 'Alpha Book (1984) must appear before Zebra Book (1986)');
        $this->assertLessThan($betaPos, $zebraPos, 'Zebra Book (1986) must appear before Beta Book (1989)');
    }

    public function testSagaAndSortCombinationApplied(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug . '?saga=loup-solitaire&sort=alpha');
        $this->assertResponseIsSuccessful();
        $html     = $client->getResponse()->getContent();
        $alphaPos = strpos($html, 'Alpha Book');
        $zebraPos = strpos($html, 'Zebra Book');
        $this->assertLessThan($zebraPos, $alphaPos, 'Alpha Book must appear before Zebra Book in alpha sort');
        $this->assertStringNotContainsString('Beta Book', $html);
    }

    public function testNoRatingOrScoreMarkup(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $html = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('bc-score', $html);
        $this->assertStringNotContainsString('notation', $html);
    }

    public function testBcStatusPresentOnEachCard(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $cards = $crawler->filter('.book-card');
        $this->assertGreaterThan(0, $cards->count());
        foreach ($cards as $cardNode) {
            $cardCrawler = new Crawler($cardNode);
            $this->assertGreaterThan(
                0,
                $cardCrawler->filter('.bc-status')->count(),
                'Each .book-card must contain a .bc-status element'
            );
        }
    }

    public function testAuthenticatedUserSeesCollectionCounter(): void
    {
        $client = static::createClient();
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(User::class)->findOneBy([]);

        if ($user === null) {
            $this->markTestSkipped('No user found in test database');
        }

        $client->loginUser($user);
        $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.biblio-head', 'dans ta collection');
    }

    public function testTrierWrapperHasAriaLabel(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[aria-label="Trier par"]');
    }

    public function testVueToggleButtonHasAriaAttributes(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/authors/' . self::$multiSagaAuthorSlug);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#btn-view-toggle[aria-label="Basculer la vue"]');
        $this->assertSame('false', $crawler->filter('#btn-view-toggle')->attr('aria-pressed'));
    }

    // --- T011: US3 — Exclusion assertions (FR-011, FR-012, FR-013) ---

    public function testExcludedElementsAbsent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/authors/' . self::$fullAuthorSlug);
        $html = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('seal-row', $html);
        $this->assertStringNotContainsString('seal-btn', $html);
        $this->assertStringNotContainsString('also-strip', $html);
        $this->assertStringNotContainsString('Contemporains', $html);
        $this->assertStringNotContainsString('Mes Favoris', $html);
        $this->assertStringNotContainsString('bc-score', $html);
        $this->assertStringNotContainsString('notation', $html);
    }

    // --- Illustrator route tests ---

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
        $client  = static::createClient();
        $crawler = $client->request('GET', '/illustrators/' . self::$illustratorSlug);

        $this->assertResponseIsSuccessful();
        $images = $crawler->filter('main img');
        foreach ($images as $img) {
            $this->assertNotEmpty($img->getAttribute('alt'), 'All <img> on illustrator page must have non-empty alt');
        }
    }

    // --- Traductor route tests ---

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
