<?php

namespace App\Tests\Service;

use App\Dto\ContributorFilterState;
use App\Repository\ContributorRepository;
use App\Service\ContributeurService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class ContributeurServiceTest extends TestCase
{
    private ContributeurService $service;
    private ContributorRepository $repo;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(ContributorRepository::class);
        $this->service = new ContributeurService($this->repo);
    }

    public function testGetPaginatedResultsDelegatesToRepository(): void
    {
        $state     = new ContributorFilterState();
        $paginator = $this->createMock(Paginator::class);

        $this->repo->expects($this->once())
            ->method('findPaginatedFiltered')
            ->with($state)
            ->willReturn($paginator);

        $this->assertSame($paginator, $this->service->getPaginatedResults($state));
    }

    public function testGetAvailableLettersDelegatesToRepository(): void
    {
        $state = new ContributorFilterState(role: 'auteur');

        $this->repo->expects($this->once())
            ->method('findAvailableLetters')
            ->with($state)
            ->willReturn(['A', 'B', 'D']);

        $this->assertSame(['A', 'B', 'D'], $this->service->getAvailableLetters($state));
    }

    public function testGetCardDataBatchMergesRepositoryResults(): void
    {
        $ids = ['uuid-1', 'uuid-2'];

        $this->repo->method('findCardDataBatch')->willReturn([
            'uuid-1' => ['bookCount' => 5, 'avgScore' => 8.2, 'roles' => ['Author']],
            'uuid-2' => ['bookCount' => 3, 'avgScore' => null, 'roles' => ['Illustrator']],
        ]);
        $this->repo->method('findTopCollectionsBatch')->willReturn([
            'uuid-1' => [['id' => 'col-a', 'nom' => 'Défis']],
            'uuid-2' => [],
        ]);

        $result = $this->service->getCardDataBatch($ids);

        $this->assertSame(5, $result['uuid-1']['bookCount']);
        $this->assertSame([['id' => 'col-a', 'nom' => 'Défis']], $result['uuid-1']['topCollections']);
        $this->assertSame([], $result['uuid-2']['topCollections']);
    }

    public function testGetCardDataBatchReturnsEmptyForNoIds(): void
    {
        $this->repo->expects($this->never())->method('findCardDataBatch');

        $result = $this->service->getCardDataBatch([]);
        $this->assertSame([], $result);
    }

    public function testGetRoleCountsDelegatesToRepository(): void
    {
        $counts = ['auteur' => 10, 'traducteur' => 5, 'illustrateur' => 8, 'tous' => 20];

        $this->repo->method('findRoleCounts')->willReturn($counts);

        $this->assertSame($counts, $this->service->getRoleCounts());
    }

    public function testGetAutocompleteResultsDelegatesToRepository(): void
    {
        $grouped = ['auteur' => [], 'traducteur' => [], 'illustrateur' => []];

        $this->repo->method('findForAutocomplete')
            ->with('jack')
            ->willReturn($grouped);

        $this->assertSame($grouped, $this->service->getAutocompleteResults('jack'));
    }

    public function testCountFilteredDelegatesToRepository(): void
    {
        $state = new ContributorFilterState(letter: 'J');

        $this->repo->method('countFiltered')->with($state)->willReturn(42);

        $this->assertSame(42, $this->service->countFiltered($state));
    }
}
