<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\DashboardData;
use App\Entity\User;
use App\Repository\ActivityEventRepository;
use App\Repository\BookRepository;
use App\Repository\ContributorRepository;
use App\Repository\SuggestionRepository;
use App\Repository\UserBookRepository;
use App\Service\DashboardService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardServiceTest extends TestCase
{
    private UserBookRepository&MockObject $userBookRepo;
    private SuggestionRepository&MockObject $suggestionRepo;
    private BookRepository&MockObject $bookRepo;
    private ContributorRepository&MockObject $contributorRepo;
    private ActivityEventRepository&MockObject $activityRepo;
    private Security&MockObject $security;
    private DashboardService $service;

    protected function setUp(): void
    {
        $this->userBookRepo = $this->createMock(UserBookRepository::class);
        $this->suggestionRepo = $this->createMock(SuggestionRepository::class);
        $this->bookRepo = $this->createMock(BookRepository::class);
        $this->contributorRepo = $this->createMock(ContributorRepository::class);
        $this->activityRepo = $this->createMock(ActivityEventRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->service = new DashboardService(
            $this->userBookRepo,
            $this->suggestionRepo,
            $this->bookRepo,
            $this->contributorRepo,
            $this->activityRepo,
            $this->security,
        );
    }

    private function buildUser(string $pseudo, ?\DateTimeImmutable $previousLoginAt = null): User
    {
        $user = new User();
        $user->setPseudo($pseudo);
        if ($previousLoginAt !== null) {
            $user->setPreviousLoginAt($previousLoginAt);
        }
        return $user;
    }

    private function stubDefaults(): void
    {
        $this->userBookRepo->method('countOwnedByUser')->willReturn(0);
        $this->userBookRepo->method('countOwnedAddedSince')->willReturn(0);
        $this->userBookRepo->method('countToReadByUser')->willReturn(0);
        $this->userBookRepo->method('countToBuyByUser')->willReturn(0);
        $this->suggestionRepo->method('countAllByUser')->willReturn(0);
        $this->suggestionRepo->method('findPendingCountByUser')->willReturn(0);
        $this->suggestionRepo->method('countRecentlyValidatedByUser')->willReturn(0);
        $this->suggestionRepo->method('countGlobalPending')->willReturn(0);
        $this->bookRepo->method('countPublished')->willReturn(0);
        $this->bookRepo->method('countPublishedSince')->willReturn(0);
        $this->bookRepo->method('findRecentlyPublished')->willReturn([]);
        $this->bookRepo->method('findAverageRatingsByIds')->willReturn([]);
        $this->contributorRepo->method('countWithPublishedBooks')->willReturn(0);
        $this->activityRepo->method('findRecentCommunity')->willReturn([]);
        $this->security->method('isGranted')->willReturn(false);
    }

    public function testGreetingFormatIsUppercase(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('marius');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame('SALUTATIONS, MARIUS.', $data->greeting);
    }

    public function testFormattedDateHasZeroPaddedDay(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertMatchesRegularExpression('/^\w+ \d{2} \w+$/u', $data->formattedDate);
    }

    public function testFormattedDateIsUppercase(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame(strtoupper($data->formattedDate), $data->formattedDate);
    }

    public function testSubtitleIsWelcomeWhenPreviousLoginAtIsNull(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('test', null);
        $data = $this->service->buildDashboardData($user);
        $this->assertStringContainsStringIgnoringCase('bienvenue', $data->headerSubtitle);
    }

    public function testSubtitleForStandardUserContainsBookCount(): void
    {
        $this->bookRepo->method('countPublishedSince')->willReturn(3);
        $user = $this->buildUser('test', new \DateTimeImmutable('-7 days'));
        $data = $this->service->buildDashboardData($user);
        $this->assertStringContainsString('3', $data->headerSubtitle);
        $this->assertStringNotContainsString('en attente', $data->headerSubtitle);
    }

    public function testSubtitleForModeratorContainsPendingSuggestions(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $role) => $role === 'ROLE_MODERATOR');
        $this->bookRepo->method('countPublishedSince')->willReturn(2);
        $this->suggestionRepo->method('countGlobalPending')->willReturn(5);
        $user = $this->buildUser('mod', new \DateTimeImmutable('-1 day'));
        $data = $this->service->buildDashboardData($user);
        $this->assertStringContainsString('5', $data->headerSubtitle);
        $this->assertStringContainsString('en attente', $data->headerSubtitle);
    }

    public function testCollectionCountAndDelta(): void
    {
        $this->userBookRepo->method('countOwnedByUser')->willReturn(12);
        $this->userBookRepo->method('countOwnedAddedSince')->willReturn(3);
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame(12, $data->collectionCount);
        $this->assertSame(3, $data->collectionDelta);
    }

    public function testZeroCollectionHasZeroDelta(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame(0, $data->collectionCount);
        $this->assertSame(0, $data->collectionDelta);
    }

    public function testSuggestionsValuesWithTodayValidations(): void
    {
        $this->suggestionRepo->method('countAllByUser')->willReturn(10);
        $this->suggestionRepo->method('findPendingCountByUser')->willReturn(4);
        $this->suggestionRepo->method('countRecentlyValidatedByUser')->willReturn(2);
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame(10, $data->suggestionsTotal);
        $this->assertSame(4, $data->suggestionsPending);
        $this->assertSame(2, $data->suggestionsValidatedRecently);
        $this->assertSame("aujourd'hui", $data->suggestionsValidatedLabel);
    }

    public function testSuggestionsValidatedLabelIsHierForYesterdayValidations(): void
    {
        $this->suggestionRepo->method('countAllByUser')->willReturn(3);
        $this->suggestionRepo->method('findPendingCountByUser')->willReturn(2);
        $this->suggestionRepo->method('countRecentlyValidatedByUser')
            ->willReturnOnConsecutiveCalls(0, 1);
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertSame(1, $data->suggestionsValidatedRecently);
        $this->assertSame('hier', $data->suggestionsValidatedLabel);
    }

    public function testIsModeratorFlagFromSecurity(): void
    {
        $this->security->method('isGranted')
            ->willReturnCallback(fn (string $role) => $role === 'ROLE_ADMIN');
        $user = $this->buildUser('admin');
        $data = $this->service->buildDashboardData($user);
        $this->assertTrue($data->isModerator);
    }

    public function testReturnsDashboardDataInstance(): void
    {
        $this->stubDefaults();
        $user = $this->buildUser('test');
        $data = $this->service->buildDashboardData($user);
        $this->assertInstanceOf(DashboardData::class, $data);
    }
}
