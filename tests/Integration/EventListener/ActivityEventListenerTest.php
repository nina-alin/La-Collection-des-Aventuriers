<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\ActivityEvent;
use App\Entity\Book;
use App\Entity\Editor;
use App\Entity\Enum\ActivityEventType;
use App\Entity\Enum\BookStatus;
use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Enum\SuggestionStatus;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Event\BookAddedToWishlistEvent;
use App\Event\BookPublishedEvent;
use App\Event\ReviewSubmittedEvent;
use App\Event\SuggestionModeratedEvent;
use App\Repository\ActivityEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ActivityEventListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EventDispatcherInterface $dispatcher;
    private ActivityEventRepository $activityRepo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->dispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        $this->activityRepo = static::getContainer()->get(ActivityEventRepository::class);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\ActivityEvent ae')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Suggestion s')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Book b WHERE b.title LIKE :prefix')
            ->setParameter('prefix', '__ael_test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Editor e WHERE e.name LIKE :prefix')
            ->setParameter('prefix', '__ael_test_%')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.email LIKE :prefix')
            ->setParameter('prefix', '__ael_test_%')
            ->execute();
        parent::tearDown();
    }

    private function createUser(string $suffix): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail("__ael_test_{$suffix}@example.com");
        $user->setPseudo("ael_{$suffix}");
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $this->em->persist($user);
        return $user;
    }

    private function createBook(string $suffix): Book
    {
        $editor = (new Editor())->setName("__ael_test_editor_{$suffix}");
        $this->em->persist($editor);
        $book = new Book();
        $book->setTitle("__ael_test_book_{$suffix}");
        $book->setStatus(BookStatus::PUBLISHED);
        $book->setEditor($editor);
        $this->em->persist($book);
        return $book;
    }

    public function testReviewSubmittedEventCreatesSOCIALActivityEvent(): void
    {
        $user = $this->createUser('reviewer');
        $book = $this->createBook('reviewed');
        $this->em->flush();

        $countBefore = count($this->activityRepo->findAll());
        $this->dispatcher->dispatch(new ReviewSubmittedEvent($user, $book));

        $events = $this->activityRepo->findAll();
        $this->assertCount($countBefore + 1, $events);

        $last = end($events);
        $this->assertSame(ActivityEventType::SOCIAL, $last->getType());
        $this->assertSame("__ael_test_book_reviewed", $last->getBookTitle());
        $this->assertSame('ael_reviewer', $last->getActorPseudo());
    }

    public function testBookPublishedEventCreatesCONTRIBUTIONActivityEvent(): void
    {
        $user = $this->createUser('publisher');
        $book = $this->createBook('published');
        $this->em->flush();

        $countBefore = count($this->activityRepo->findAll());
        $this->dispatcher->dispatch(new BookPublishedEvent($user, $book));

        $events = $this->activityRepo->findAll();
        $this->assertCount($countBefore + 1, $events);

        $last = end($events);
        $this->assertSame(ActivityEventType::CONTRIBUTION, $last->getType());
        $this->assertSame("__ael_test_book_published", $last->getBookTitle());
    }

    public function testSuggestionModeratedEventCreatesMODERATIONActivityEvent(): void
    {
        $mod = $this->createUser('moderator');
        $suggUser = $this->createUser('sugguser');
        $this->em->flush();

        $suggestion = new Suggestion();
        $suggestion->setUser($suggUser);
        $suggestion->setEntityType(SuggestionEntityType::BOOK);
        $suggestion->setMode(SuggestionMode::NEW_ENTRY);
        $suggestion->setFormData(['title' => 'Test Suggestion Book']);
        $this->em->persist($suggestion);
        $this->em->flush();

        $countBefore = count($this->activityRepo->findAll());
        $this->dispatcher->dispatch(new SuggestionModeratedEvent($mod, $suggestion, SuggestionStatus::VALIDATED));

        $events = $this->activityRepo->findAll();
        $this->assertCount($countBefore + 1, $events);

        $last = end($events);
        $this->assertSame(ActivityEventType::MODERATION, $last->getType());
        $this->assertSame('VALIDATED', $last->getStatusBadge());
    }

    public function testBookAddedToWishlistEventCreatesPERSONALActivityEvent(): void
    {
        $user = $this->createUser('wisher');
        $book = $this->createBook('wished');
        $this->em->flush();

        $countBefore = count($this->activityRepo->findAll());
        $this->dispatcher->dispatch(new BookAddedToWishlistEvent($user, $book));

        $events = $this->activityRepo->findAll();
        $this->assertCount($countBefore + 1, $events);

        $last = end($events);
        $this->assertSame(ActivityEventType::PERSONAL, $last->getType());
        $this->assertSame("__ael_test_book_wished", $last->getBookTitle());
    }
}
