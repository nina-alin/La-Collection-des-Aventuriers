<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Search\SearchResultItem;
use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use App\Repository\BookRepository;
use App\Repository\CollectionRepository;
use App\Repository\ContributorRepository;

class GlobalSearchService
{
    private const AVATAR_COLORS = ['cuir', 'mousse', 'encre', 'sang', 'or'];
    private const MAX_RESULTS = 8;

    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly CollectionRepository $collectionRepository,
        private readonly ContributorRepository $contributorRepository,
    ) {}

    /** @return SearchResultItem[] */
    public function query(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }

        $books = $this->bookRepository->findForGlobalSearch($q);
        $collections = $this->collectionRepository->findForGlobalSearch($q);
        $contributors = $this->contributorRepository->findForGlobalSearch($q);

        $items = array_merge(
            array_map($this->mapBook(...), $books),
            array_map($this->mapCollection(...), $collections),
            array_map($this->mapContributor(...), $contributors),
        );

        return array_slice($items, 0, self::MAX_RESULTS);
    }

    /** @return SearchResultItem[] */
    public function findPopular(): array
    {
        $books = [];
        $collections = [];
        $contributors = [];

        try {
            $books = array_map($this->mapBook(...), $this->bookRepository->findMostPopular(4));
        } catch (\Throwable) {}

        try {
            $collections = array_map($this->mapCollection(...), $this->collectionRepository->findMostPopular(2));
        } catch (\Throwable) {}

        try {
            $contributors = array_map($this->mapContributor(...), $this->contributorRepository->findMostPopular(2));
        } catch (\Throwable) {}

        return array_slice(array_merge($books, $collections, $contributors), 0, 4);
    }

    private function mapBook(Book $book): SearchResultItem
    {
        return new SearchResultItem(
            type: 'livre',
            slug: (string) $book->getSlug(),
            title: $book->getTitle(),
            subtitle: $this->bookSubtitle($book),
            thumbnailUrl: $book->getCoverImage() !== null ? '/uploads/covers/' . $book->getCoverImage() : null,
            initials: null,
            avatarColor: null,
        );
    }

    private function mapCollection(Collection $collection): SearchResultItem
    {
        $bookCount = $collection->getBooks()->count();
        $author = $collection->getCreateurs()[0] ?? null;

        $parts = ['collection', $bookCount . ' tomes'];
        if ($author !== null && $author !== '') {
            $parts[] = (string) $author;
        }

        return new SearchResultItem(
            type: 'collection',
            slug: $collection->getSlug(),
            title: $collection->getNom(),
            subtitle: implode(' · ', $parts),
            thumbnailUrl: null,
            initials: null,
            avatarColor: null,
        );
    }

    private function mapContributor(Contributor $contributor): SearchResultItem
    {
        $ficheCount = $contributor->getContributions()->count();

        return new SearchResultItem(
            type: 'auteur',
            slug: $contributor->getSlug(),
            title: trim($contributor->getFirstName() . ' ' . $contributor->getLastName()),
            subtitle: 'auteur · ' . $ficheCount . ' fiches',
            thumbnailUrl: null,
            initials: $this->initialsFor($contributor),
            avatarColor: $this->avatarColorForSlug($contributor->getSlug()),
        );
    }

    private function bookSubtitle(Book $book): string
    {
        $parts = [];

        if ($book->getIsbn() !== null) {
            $parts[] = $book->getIsbn();
        }

        if ($book->getFrenchPublicationYear() !== null) {
            $parts[] = (string) $book->getFrenchPublicationYear();
        }

        $authorName = $this->bookAuthorName($book);
        if ($authorName !== null) {
            $parts[] = $authorName;
        }

        return implode(' · ', $parts);
    }

    private function bookAuthorName(Book $book): ?string
    {
        foreach ($book->getContributions() as $contribution) {
            if ($contribution->getRole() === ContributionRole::Author) {
                $c = $contribution->getContributor();
                return trim($c->getFirstName() . ' ' . $c->getLastName());
            }
        }

        return $book->getEditor()?->getName();
    }

    private function initialsFor(Contributor $contributor): string
    {
        $first = mb_strtoupper(mb_substr($contributor->getFirstName(), 0, 1));
        $last = mb_strtoupper(mb_substr($contributor->getLastName(), 0, 1));
        return $first . $last;
    }

    private function avatarColorForSlug(string $slug): string
    {
        return self::AVATAR_COLORS[abs(crc32($slug)) % 5];
    }
}
