<?php

declare(strict_types=1);

namespace App\Messenger\Handler;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Messenger\Message\BookFollowJob;
use App\Repository\BookRepository;
use App\Repository\UserFollowedContributorRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookFollowJobHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookRepository $bookRepository,
        private readonly UserFollowedContributorRepository $followRepository,
    ) {}

    public function __invoke(BookFollowJob $job): void
    {
        $book = $this->bookRepository->find($job->bookId);
        if ($book === null) {
            return;
        }

        if ($book->getFollowNotificationSentAt() !== null) {
            return;
        }

        $book->setFollowNotificationSentAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->em->persist($book);
        $this->em->flush();

        $recipients = $this->followRepository->findRecipientsForBook($book);

        foreach ($recipients as $recipient) {
            $user         = $recipient['user'];
            $templateType = $recipient['templateType'];
            $contributor  = $recipient['contributor'];
            $collection   = $recipient['collection'];

            if ($templateType === 'contributor' && $contributor !== null) {
                $notifMessage = sprintf(
                    '%s a publié un nouveau livre, écrit par %s %s que vous suivez.',
                    $book->getTitle(),
                    $contributor->getFirstName(),
                    $contributor->getLastName()
                );
                $targetUrl = $book->getCollection()
                    ? '/collections/' . $book->getCollection()->getSlug()
                    : null;
            } else {
                $collectionNom = $collection?->getNom() ?? '';
                $notifMessage = sprintf(
                    '%s a été publié dans la collection %s que vous suivez.',
                    $book->getTitle(),
                    $collectionNom
                );
                $targetUrl = $collection ? '/collections/' . $collection->getSlug() : null;
            }

            $sourceId = 'follow_book_' . $book->getId();

            $notification = new Notification(
                $user,
                NotificationType::FOLLOW_NOVELTY,
                $notifMessage,
                $sourceId,
                $targetUrl,
            );

            try {
                $this->em->persist($notification);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        $this->em->flush();
    }
}
