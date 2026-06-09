<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\LandingStatsDto;
use App\Dto\MarqueeItemDto;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;
use App\Repository\UserRepository;

class LandingService
{
    private const BOOK_COLORS = ['bg-cuir', 'bg-mousse', 'bg-encre', 'bg-sang', 'bg-or'];

    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly CollectionRepository $collectionRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function getStats(): LandingStatsDto
    {
        return new LandingStatsDto(
            totalBooks: $this->bookRepository->countPublished(),
            totalUsers: $this->userRepository->countActive(),
            newThisWeek: $this->bookRepository->countPublishedSince(
                new \DateTimeImmutable('-7 days', new \DateTimeZone('UTC'))
            ),
            totalContributors: $this->contributorRepository->countWithPublishedBooks(),
        );
    }

    /** @return MarqueeItemDto[] */
    public function getMarqueeItems(): array
    {
        $items = [];
        $colorIndex = 0;

        foreach ($this->bookRepository->findMostPopular(10) as $book) {
            $slug = $book->getSlug();
            if ($slug === null || $slug === '') {
                continue;
            }

            $year = $book->getFrenchPublicationYear();
            $subtitle = $year !== null ? "Livre · {$year}" : 'Livre';
            $title = $book->getTitle();
            $initials = mb_substr(explode(' ', $title)[0], 0, 8);

            $items[] = new MarqueeItemDto(
                name: $title,
                type: 'book',
                url: "/livre/{$slug}",
                subtitle: $subtitle,
                initials: $initials,
                colorClass: self::BOOK_COLORS[$colorIndex % count(self::BOOK_COLORS)],
            );
            ++$colorIndex;
        }

        foreach ($this->contributorRepository->findMostPopular(10) as $contributor) {
            $slug = $contributor->getSlug();
            if ($slug === '') {
                continue;
            }

            $pseudo = $contributor->getPseudo();
            $name = $pseudo !== null && $pseudo !== ''
                ? $pseudo
                : trim($contributor->getFirstName() . ' ' . $contributor->getLastName());

            $firstName = $contributor->getFirstName();
            $lastName = $contributor->getLastName();
            $initials = mb_strtoupper(
                (mb_strlen($firstName) > 0 ? mb_substr($firstName, 0, 1) : '') .
                (mb_strlen($lastName) > 0 ? mb_substr($lastName, 0, 1) : '')
            );

            $contributionCount = count($contributor->getContributions());

            $items[] = new MarqueeItemDto(
                name: $name,
                type: 'author',
                url: "/authors/{$slug}",
                subtitle: "Auteur · {$contributionCount} œuvres",
                initials: $initials,
                colorClass: 'is-author',
            );
        }

        foreach ($this->collectionRepository->findMostPopular(10) as $collection) {
            $slug = $collection->getSlug();
            if ($slug === '') {
                continue;
            }

            $nom = $collection->getNom();
            $initials = mb_substr(explode(' ', $nom)[0], 0, 8);
            $bookCount = count($collection->getBooks());

            $items[] = new MarqueeItemDto(
                name: $nom,
                type: 'collection',
                url: "/collections/{$slug}",
                subtitle: "Collection · {$bookCount} tomes",
                initials: $initials,
                colorClass: 'bg-grimoire',
            );
        }

        shuffle($items);

        return array_slice($items, 0, 30);
    }
}
