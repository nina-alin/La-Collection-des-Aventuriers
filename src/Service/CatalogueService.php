<?php

namespace App\Service;

use App\Dto\ActiveFilterState;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserBookRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CatalogueService
{
    public function __construct(
        private readonly BookRepository     $bookRepository,
        private readonly UserBookRepository $userBookRepository,
    ) {}

    /** @return array{min: int, max: int} */
    public function getParagraphBounds(): array
    {
        return $this->bookRepository->findParagraphBounds();
    }

    public function getFilteredResults(ActiveFilterState $state, ?User $user = null): Paginator
    {
        return $this->bookRepository->findFilteredPaginated($state, $user);
    }

    /**
     * Returns a map of bookId → UserBook for the given page's book IDs.
     *
     * @param int[] $bookIds
     * @return array<int, \App\Entity\UserBook>
     */
    public function getUserBooksForPage(?User $user, array $bookIds): array
    {
        if ($user === null || empty($bookIds)) {
            return [];
        }

        $userBooks = $this->userBookRepository->findByUserAndBookIds($user, $bookIds);
        $map = [];
        foreach ($userBooks as $userBook) {
            $map[$userBook->getBook()->getId()] = $userBook;
        }

        return $map;
    }
}
