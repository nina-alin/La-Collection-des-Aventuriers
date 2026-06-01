<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use App\Repository\CollectionPublishingHistoryRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributionRepository;
use App\Service\CollectionService;
use App\ValueObject\ContributorPill;
use App\ValueObject\HeroMeta;
use App\ValueObject\RecurringContributorsResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CollectionServiceTest extends TestCase
{
    private CollectionRepository&MockObject $collectionRepo;
    private ContributionRepository&MockObject $contributionRepo;
    private CollectionPublishingHistoryRepository&MockObject $historyRepo;
    private CollectionService $service;

    protected function setUp(): void
    {
        $this->collectionRepo = $this->createMock(CollectionRepository::class);
        $this->contributionRepo = $this->createMock(ContributionRepository::class);
        $this->historyRepo = $this->createMock(CollectionPublishingHistoryRepository::class);

        $this->service = new CollectionService(
            $this->collectionRepo,
            $this->contributionRepo,
            $this->historyRepo,
        );
    }

    private function makeCollection(): Collection
    {
        $c = new Collection();
        $c->setNom('Test');
        $c->setDescription('Desc');
        $c->setGenre(GenreCollection::AVENTURE);
        $c->setStatut(StatutCollection::EN_COURS);
        return $c;
    }

    private function makeContributor(string $firstName, string $lastName): Contributor
    {
        $c = $this->createMock(Contributor::class);
        $c->method('getId')->willReturn(Uuid::v7());
        $c->method('getFirstName')->willReturn($firstName);
        $c->method('getLastName')->willReturn($lastName);
        return $c;
    }

    public function testGetHeroMetaReturnsCorrectValues(): void
    {
        $collection = $this->makeCollection();
        $this->collectionRepo->method('getPublicationYearRange')
            ->willReturn(['min' => 1984, 'max' => 1998]);
        $this->collectionRepo->method('computeAverageRating')
            ->willReturn(8.7);

        $heroMeta = $this->service->getHeroMeta($collection);

        $this->assertInstanceOf(HeroMeta::class, $heroMeta);
        $this->assertSame(1984, $heroMeta->yearMin);
        $this->assertSame(1998, $heroMeta->yearMax);
        $this->assertSame(8.7, $heroMeta->averageRating);
    }

    public function testGetHeroMetaWithNullsReturnsNulls(): void
    {
        $collection = $this->makeCollection();
        $this->collectionRepo->method('getPublicationYearRange')
            ->willReturn(['min' => null, 'max' => null]);
        $this->collectionRepo->method('computeAverageRating')
            ->willReturn(null);

        $heroMeta = $this->service->getHeroMeta($collection);

        $this->assertNull($heroMeta->yearMin);
        $this->assertNull($heroMeta->yearMax);
        $this->assertNull($heroMeta->averageRating);
    }

    public function testGetRecurringContributorsDeduplicatesUniqueCount(): void
    {
        $collection = $this->makeCollection();
        $joe = $this->makeContributor('Joe', 'Dever');
        $gary = $this->makeContributor('Gary', 'Chalk');

        $rows = [
            ['contributor' => $joe, 'role' => ContributionRole::Author, 'count' => 28],
            ['contributor' => $gary, 'role' => ContributionRole::Illustrator, 'count' => 7],
            ['contributor' => $joe, 'role' => ContributionRole::Illustrator, 'count' => 3],
        ];

        $this->contributionRepo->method('findRecurringByCollection')->willReturn($rows);

        $result = $this->service->getRecurringContributors($collection);

        $this->assertInstanceOf(RecurringContributorsResult::class, $result);
        $this->assertSame(2, $result->uniqueCount);
        $this->assertCount(3, $result->pills);
    }

    public function testInitialsJoeDever(): void
    {
        $collection = $this->makeCollection();
        $joe = $this->makeContributor('Joe', 'Dever');

        $this->contributionRepo->method('findRecurringByCollection')->willReturn([
            ['contributor' => $joe, 'role' => ContributionRole::Author, 'count' => 28],
        ]);

        $result = $this->service->getRecurringContributors($collection);

        $this->assertSame('JD', $result->pills[0]->initials);
    }

    public function testInitialsSingleName(): void
    {
        $collection = $this->makeCollection();
        $joeCont = $this->makeContributor('Jo', '');

        $this->contributionRepo->method('findRecurringByCollection')->willReturn([
            ['contributor' => $joeCont, 'role' => ContributionRole::Author, 'count' => 1],
        ]);

        $result = $this->service->getRecurringContributors($collection);

        $this->assertSame('JO', $result->pills[0]->initials);
    }

    public function testPillsOrderedByCountDesc(): void
    {
        $collection = $this->makeCollection();
        $c1 = $this->makeContributor('First', 'One');
        $c2 = $this->makeContributor('Second', 'Two');

        $rows = [
            ['contributor' => $c1, 'role' => ContributionRole::Author, 'count' => 28],
            ['contributor' => $c2, 'role' => ContributionRole::Illustrator, 'count' => 7],
        ];

        $this->contributionRepo->method('findRecurringByCollection')->willReturn($rows);

        $result = $this->service->getRecurringContributors($collection);

        $this->assertSame(28, $result->pills[0]->count);
        $this->assertSame(7, $result->pills[1]->count);
    }

    public function testEmptyContributorsResult(): void
    {
        $collection = $this->makeCollection();
        $this->contributionRepo->method('findRecurringByCollection')->willReturn([]);

        $result = $this->service->getRecurringContributors($collection);

        $this->assertSame(0, $result->uniqueCount);
        $this->assertSame([], $result->pills);
    }
}
