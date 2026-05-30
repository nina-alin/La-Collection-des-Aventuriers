<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\BookStatus;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ReviewControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\Review r')->execute();
        $em->createQuery('DELETE FROM App\Entity\BookImage bi')->execute();
        $em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $em->createQuery('DELETE FROM App\Entity\Editor e')->execute();
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
        parent::tearDown();
    }

    private function createUser(array $roles, string $email, string $pseudo): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createBook(string $title): Book
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $editor = (new Editor())->setName('Test Editor ' . uniqid());
        $em->persist($editor);

        $book = new Book();
        $book->setTitle($title);
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        $em->persist($book);
        $em->flush();

        return $book;
    }

    private function csrfToken(object $client, string $tokenId): string
    {
        $client->request('GET', '/test-tokens/csrf/' . $tokenId);
        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    // --- SUBMIT SCENARIOS ---

    public function testUnauthenticatedSubmitRedirectsToLogin(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');

        $client->request('POST', '/livre/' . $book->getSlug() . '/avis', ['score' => 7]);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('connexion', $client->getResponse()->headers->get('Location'));
    }

    public function testMissingScoreReturns422(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'user@example.com', 'testuser');
        $client->loginUser($user);

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('POST', '/livre/' . $book->getSlug() . '/avis', [
            '_token' => $token,
            'comment' => 'Nice',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testValidSubmitCreatesReviewAndRedirects(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'user@example.com', 'testuser');
        $client->loginUser($user);

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('POST', '/livre/' . $book->getSlug() . '/avis', [
            '_token' => $token,
            'score' => '8',
            'comment' => 'Great book!',
        ]);

        $this->assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = $em->getRepository(Review::class)->findOneBy(['user' => $user, 'book' => $book]);
        $this->assertNotNull($review);
        $this->assertSame(8, $review->getScore());
        $this->assertSame('Great book!', $review->getComment());
    }

    public function testDuplicateSubmitUpdatesExistingReview(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'user@example.com', 'testuser');
        $client->loginUser($user);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(5);
        $review->setBook($book);
        $review->setUser($user);
        $em->persist($review);
        $em->flush();
        $reviewId = $review->getId();

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('POST', '/livre/' . $book->getSlug() . '/avis', [
            '_token' => $token,
            'score' => '9',
            'comment' => 'Updated!',
        ]);

        $this->assertResponseRedirects();

        $em->clear();
        $updatedReview = $em->getRepository(Review::class)->find($reviewId);
        $this->assertNotNull($updatedReview);
        $this->assertSame(9, $updatedReview->getScore());

        $allReviews = $em->getRepository(Review::class)->findBy(['user' => $user, 'book' => $book]);
        $this->assertCount(1, $allReviews);
    }

    public function testMissingCsrfOnSubmitReturns422(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'user@example.com', 'testuser');
        $client->loginUser($user);

        $client->request('POST', '/livre/' . $book->getSlug() . '/avis', [
            '_token' => 'invalid-token',
            'score' => '7',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    // --- DELETE SCENARIOS ---

    public function testUnauthenticatedDeleteRedirectsToLogin(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'owner@example.com', 'owner');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(7);
        $review->setBook($book);
        $review->setUser($user);
        $em->persist($review);
        $em->flush();

        $client->request('DELETE', '/livre/' . $book->getSlug() . '/avis/' . $review->getId());

        $this->assertResponseRedirects();
        $this->assertStringContainsString('connexion', $client->getResponse()->headers->get('Location'));
    }

    public function testAuthorCanDeleteOwnReview(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'owner@example.com', 'owner');
        $client->loginUser($user);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(7);
        $review->setBook($book);
        $review->setUser($user);
        $em->persist($review);
        $em->flush();
        $reviewId = $review->getId();

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('DELETE', '/livre/' . $book->getSlug() . '/avis/' . $reviewId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        $em->clear();
        $deleted = $em->getRepository(Review::class)->find($reviewId);
        $this->assertNull($deleted);
    }

    public function testModeratorCanDeleteAnotherUsersReview(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $owner = $this->createUser([], 'owner@example.com', 'owner');
        $mod = $this->createUser(['ROLE_MODERATOR'], 'mod@example.com', 'moduser');
        $client->loginUser($mod);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(5);
        $review->setBook($book);
        $review->setUser($owner);
        $em->persist($review);
        $em->flush();
        $reviewId = $review->getId();

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('DELETE', '/livre/' . $book->getSlug() . '/avis/' . $reviewId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects();

        $em->clear();
        $deleted = $em->getRepository(Review::class)->find($reviewId);
        $this->assertNull($deleted);
    }

    public function testRoleUserCannotDeleteAnotherUsersReview(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $owner = $this->createUser([], 'owner@example.com', 'owner');
        $other = $this->createUser([], 'other@example.com', 'otheruser');
        $client->loginUser($other);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(5);
        $review->setBook($book);
        $review->setUser($owner);
        $em->persist($review);
        $em->flush();

        $token = $this->csrfToken($client, 'review_submit');
        $client->request('DELETE', '/livre/' . $book->getSlug() . '/avis/' . $review->getId(), [
            '_token' => $token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMissingCsrfOnDeleteReturns422(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'owner@example.com', 'owner');
        $client->loginUser($user);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $review = new Review();
        $review->setScore(7);
        $review->setBook($book);
        $review->setUser($user);
        $em->persist($review);
        $em->flush();

        $client->request('DELETE', '/livre/' . $book->getSlug() . '/avis/' . $review->getId(), [
            '_token' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    // --- LIST / FILTER SCENARIOS ---

    public function testDefaultFilterReturnsAllReviewsSortedByUpdatedAt(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');

        $client->request('GET', '/livre/' . $book->getSlug() . '/avis');

        $this->assertResponseIsSuccessful();
    }

    public function testFilterAvecCommentaireExcludesNullComments(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');
        $user = $this->createUser([], 'user@example.com', 'testuser');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $reviewWithComment = new Review();
        $reviewWithComment->setScore(8);
        $reviewWithComment->setBook($book);
        $reviewWithComment->setUser($user);
        $reviewWithComment->setComment('Great!');
        $em->persist($reviewWithComment);

        $em->flush();

        $client->request('GET', '/livre/' . $book->getSlug() . '/avis?filter=avec_commentaire');

        $this->assertResponseIsSuccessful();
    }

    public function testEmptyResultRendersEmptyState(): void
    {
        $client = static::createClient();
        $book = $this->createBook('Test Book');

        $client->request('GET', '/livre/' . $book->getSlug() . '/avis');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('[data-empty-state]', 'Aucune évaluation');
    }
}
