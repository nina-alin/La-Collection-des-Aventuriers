<?php

namespace App\Tests\Dto;

use App\Dto\ContributorFilterState;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ContributorFilterStateTest extends TestCase
{
    public function testDefaultsFromEmptyRequest(): void
    {
        $state = ContributorFilterState::fromRequest(new Request());

        $this->assertSame('tous', $state->role);
        $this->assertNull($state->letter);
        $this->assertSame([], $state->collectionIds);
        $this->assertNull($state->periodMin);
        $this->assertNull($state->periodMax);
        $this->assertNull($state->nationality);
        $this->assertNull($state->bookCountRange);
        $this->assertFalse($state->onlyFollowed);
        $this->assertSame('az', $state->sort);
        $this->assertSame(1, $state->page);
    }

    public function testInvalidRoleFallsBackToTous(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['role' => 'badvalue']));
        $this->assertSame('tous', $state->role);
    }

    public function testValidRoleAccepted(): void
    {
        foreach (['tous', 'auteur', 'traducteur', 'illustrateur'] as $role) {
            $state = ContributorFilterState::fromRequest(new Request(['role' => $role]));
            $this->assertSame($role, $state->role);
        }
    }

    public function testInvalidLetterRejected(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['letter' => '1']));
        $this->assertNull($state->letter);

        $state = ContributorFilterState::fromRequest(new Request(['letter' => 'AB']));
        $this->assertNull($state->letter);
    }

    public function testValidLetterNormalized(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['letter' => 'a']));
        $this->assertSame('A', $state->letter);
    }

    public function testInvalidSortFallsBackToAz(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['sort' => 'invalid']));
        $this->assertSame('az', $state->sort);
    }

    public function testValidSortsAccepted(): void
    {
        foreach (['az', 'ouvrages', 'note'] as $sort) {
            $state = ContributorFilterState::fromRequest(new Request(['sort' => $sort]));
            $this->assertSame($sort, $state->sort);
        }
    }

    public function testPageMinimumEnforcedAtOne(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['page' => '0']));
        $this->assertSame(1, $state->page);

        $state = ContributorFilterState::fromRequest(new Request(['page' => '-5']));
        $this->assertSame(1, $state->page);
    }

    public function testPageAcceptsPositiveInt(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['page' => '3']));
        $this->assertSame(3, $state->page);
    }

    public function testInvalidBookCountRangeRejected(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['bookCountRange' => 'bad']));
        $this->assertNull($state->bookCountRange);
    }

    public function testValidBookCountRangeAccepted(): void
    {
        foreach (['1-5', '6-15', '16-30', '30+'] as $range) {
            $state = ContributorFilterState::fromRequest(new Request(['bookCountRange' => $range]));
            $this->assertSame($range, $state->bookCountRange);
        }
    }

    public function testToUrlParamsOmitsDefaults(): void
    {
        $state = new ContributorFilterState();
        $this->assertSame([], $state->toUrlParams());
    }

    public function testToUrlParamsIncludesNonDefaults(): void
    {
        $state = new ContributorFilterState(
            role: 'auteur',
            letter: 'J',
            sort: 'note',
            page: 3,
        );
        $params = $state->toUrlParams();

        $this->assertSame('auteur', $params['role']);
        $this->assertSame('J', $params['letter']);
        $this->assertSame('note', $params['sort']);
        $this->assertSame(3, $params['page']);
    }

    public function testToUrlParamsOmitsPageOne(): void
    {
        $state = new ContributorFilterState(page: 1);
        $this->assertArrayNotHasKey('page', $state->toUrlParams());
    }

    public function testPeriodMinMaxSwappedWhenInverted(): void
    {
        $state = ContributorFilterState::fromRequest(new Request(['periodMin' => '2000', 'periodMax' => '1980']));
        $this->assertSame(1980, $state->periodMin);
        $this->assertSame(2000, $state->periodMax);
    }
}
