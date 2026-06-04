<?php

namespace App\Service;

use App\Dto\DashboardData;
use App\Entity\User;
use App\Repository\ActivityEventRepository;
use App\Repository\BookRepository;
use App\Repository\ContributorRepository;
use App\Repository\SuggestionRepository;
use App\Repository\UserBookRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardService
{
    public function __construct(
        private readonly UserBookRepository $userBookRepository,
        private readonly SuggestionRepository $suggestionRepository,
        private readonly BookRepository $bookRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly ActivityEventRepository $activityEventRepository,
        private readonly Security $security,
    ) {}

    public function buildDashboardData(User $user): DashboardData
    {
        $errors = [];

        $collectionCount = 0;
        $collectionDelta = 0;
        $toReadCount = 0;
        $toBuyCount = 0;
        try {
            $collectionCount = $this->userBookRepository->countOwnedByUser($user);
            $since30d = new \DateTimeImmutable('-30 days', new \DateTimeZone('UTC'));
            $collectionDelta = $this->userBookRepository->countOwnedAddedSince($user, $since30d);
            $toReadCount = $this->userBookRepository->countToReadByUser($user);
            $toBuyCount = $this->userBookRepository->countToBuyByUser($user);
        } catch (\Throwable $e) {
            $errors[] = 'kpis';
        }

        $suggestionsTotal = 0;
        $suggestionsPending = 0;
        $suggestionsValidatedRecently = 0;
        $suggestionsValidatedLabel = '';
        $globalPendingSuggestions = 0;
        try {
            $suggestionsTotal = $this->suggestionRepository->countAllByUser($user);
            $suggestionsPending = $this->suggestionRepository->findPendingCountByUser($user);
            $since24h = new \DateTimeImmutable('-24 hours', new \DateTimeZone('UTC'));
            $todayMidnight = new \DateTimeImmutable('today midnight', new \DateTimeZone('UTC'));
            $validatedToday = $this->suggestionRepository->countRecentlyValidatedByUser($user, $todayMidnight);
            if ($validatedToday > 0) {
                $suggestionsValidatedRecently = $validatedToday;
                $suggestionsValidatedLabel = "aujourd'hui";
            } else {
                $suggestionsValidatedRecently = $this->suggestionRepository->countRecentlyValidatedByUser($user, $since24h);
                $suggestionsValidatedLabel = $suggestionsValidatedRecently > 0 ? 'hier' : '';
            }
            $globalPendingSuggestions = $this->suggestionRepository->countGlobalPending();
        } catch (\Throwable $e) {
            $errors[] = 'suggestions';
        }

        $catalogueBookCount = 0;
        $catalogueAuthorCount = 0;
        try {
            $catalogueBookCount = $this->bookRepository->countPublished();
            $catalogueAuthorCount = $this->contributorRepository->countWithPublishedBooks();
        } catch (\Throwable $e) {
            $errors[] = 'catalogue';
        }

        $recentBooks = [];
        $averageRatings = [];
        try {
            $recentBooks = $this->bookRepository->findRecentlyPublished(5);
            $bookIds = array_filter(array_map(fn ($b) => $b->getId(), $recentBooks));
            if (!empty($bookIds)) {
                $averageRatings = $this->bookRepository->findAverageRatingsByIds($bookIds);
            }
        } catch (\Throwable $e) {
            $errors[] = 'nouveautes';
        }

        $activityEvents = [];
        try {
            $activityEvents = $this->activityEventRepository->findRecentCommunity(10);
        } catch (\Throwable $e) {
            $errors[] = 'activite';
        }

        $isModerator = $this->security->isGranted('ROLE_MODERATOR') || $this->security->isGranted('ROLE_ADMIN');

        $headerSubtitle = $this->buildHeaderSubtitle($user, $globalPendingSuggestions, $isModerator);

        $pseudo = strtoupper($user->getPseudo());
        $greeting = sprintf('SALUTATIONS, %s.', $pseudo);
        $formattedDate = $this->buildFormattedDate();

        return new DashboardData(
            greeting: $greeting,
            formattedDate: $formattedDate,
            headerSubtitle: $headerSubtitle,
            collectionCount: $collectionCount,
            collectionDelta: $collectionDelta,
            toReadCount: $toReadCount,
            toBuyCount: $toBuyCount,
            suggestionsTotal: $suggestionsTotal,
            suggestionsPending: $suggestionsPending,
            suggestionsValidatedRecently: $suggestionsValidatedRecently,
            suggestionsValidatedLabel: $suggestionsValidatedLabel,
            catalogueBookCount: $catalogueBookCount,
            catalogueAuthorCount: $catalogueAuthorCount,
            libraryBookCount: $collectionCount,
            libraryToReadCount: $toReadCount,
            wishlistCount: $toBuyCount,
            globalPendingSuggestions: $globalPendingSuggestions,
            recentBooks: $recentBooks,
            averageRatings: $averageRatings,
            activityEvents: $activityEvents,
            isModerator: $isModerator,
            errors: $errors,
        );
    }

    private function buildFormattedDate(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $frDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $frMonths = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $dayName = $frDays[(int) $now->format('N') - 1];
        $dayNum = $now->format('d');
        $monthName = $frMonths[(int) $now->format('n') - 1];
        return strtoupper(sprintf('%s %s %s', $dayName, $dayNum, $monthName));
    }

    private function buildHeaderSubtitle(User $user, int $globalPendingSuggestions, bool $isModerator): string
    {
        $previousLoginAt = $user->getPreviousLoginAt();

        if ($previousLoginAt === null) {
            return 'Bienvenue dans ta collection. Explore, découvre, contribue !';
        }

        try {
            $newBooksCount = $this->bookRepository->countPublishedSince($previousLoginAt);
        } catch (\Throwable) {
            $newBooksCount = 0;
        }

        $bookPart = sprintf('%d nouvelle%s fiche%s depuis ta dernière visite', $newBooksCount, $newBooksCount > 1 ? 's' : '', $newBooksCount > 1 ? 's' : '');

        if ($isModerator) {
            return sprintf('%s · %d suggestion%s en attente', $bookPart, $globalPendingSuggestions, $globalPendingSuggestions > 1 ? 's' : '');
        }

        return $bookPart;
    }
}
