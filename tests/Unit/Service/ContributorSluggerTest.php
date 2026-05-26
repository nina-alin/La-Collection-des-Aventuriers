<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use App\Service\ContributorSlugger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ContributorSluggerTest extends TestCase
{
    private function makeSlugger(?Contributor $existing = null): ContributorSlugger
    {
        $repo = $this->createMock(ContributorRepository::class);

        if ($existing !== null) {
            $repo->method('findOneBy')->willReturnCallback(function (array $criteria) use ($existing): ?Contributor {
                if ($criteria['slug'] === $existing->getSlug()) {
                    return $existing;
                }
                return null;
            });
        } else {
            $repo->method('findOneBy')->willReturn(null);
        }

        return new ContributorSlugger(new AsciiSlugger(), $repo);
    }

    public function testPseudoUsedAsSlugSourceWhenNonNull(): void
    {
        $contributor = new Contributor();
        $contributor->setFirstName('Steve');
        $contributor->setLastName('Jackson');
        $contributor->setPseudo('SJ');

        $slugger = $this->makeSlugger();
        $this->assertSame('sj', $slugger->generateUnique($contributor));
    }

    public function testFirstNameLastNameUsedWhenPseudoNull(): void
    {
        $contributor = new Contributor();
        $contributor->setFirstName('Steve');
        $contributor->setLastName('Jackson');

        $slugger = $this->makeSlugger();
        $this->assertSame('steve-jackson', $slugger->generateUnique($contributor));
    }

    public function testAccentedCharactersStripped(): void
    {
        $contributor = new Contributor();
        $contributor->setFirstName('Élodie');
        $contributor->setLastName('Ümann');

        $slugger = $this->makeSlugger();
        $this->assertSame('elodie-umann', $slugger->generateUnique($contributor));
    }

    public function testCollisionSuffixAppended(): void
    {
        $existingContributor = new Contributor();
        $existingContributor->setFirstName('John');
        $existingContributor->setLastName('Doe');
        $existingContributor->setSlug('john-doe');

        $repo = $this->createMock(ContributorRepository::class);
        $repo->method('findOneBy')->willReturnCallback(function (array $criteria) use ($existingContributor): ?Contributor {
            return $criteria['slug'] === 'john-doe' ? $existingContributor : null;
        });

        $slugger = new ContributorSlugger(new AsciiSlugger(), $repo);

        $newContributor = new Contributor();
        $newContributor->setFirstName('John');
        $newContributor->setLastName('Doe');

        $this->assertSame('john-doe-2', $slugger->generateUnique($newContributor));
    }
}
