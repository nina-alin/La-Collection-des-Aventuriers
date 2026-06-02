<?php

namespace App\Twig\Components\Book;

use App\Entity\Book;
use App\Repository\UserBookRepository;
use App\Service\UserBookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class LibraryActionsComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public Book $book;

    public function __construct(
        private readonly UserBookRepository $userBookRepository,
        private readonly UserBookService $userBookService,
    ) {}

    public function isOwned(): bool
    {
        $userBook = $this->userBookRepository->findByUserAndBook($this->getUser(), $this->book);
        return $userBook?->isOwned() ?? false;
    }

    public function isToRead(): bool
    {
        $userBook = $this->userBookRepository->findByUserAndBook($this->getUser(), $this->book);
        return $userBook?->isToRead() ?? false;
    }

    public function isToBuy(): bool
    {
        $userBook = $this->userBookRepository->findByUserAndBook($this->getUser(), $this->book);
        return $userBook?->isToBuy() ?? false;
    }

    public function isFavorite(): bool
    {
        $userBook = $this->userBookRepository->findByUserAndBook($this->getUser(), $this->book);
        return $userBook?->isFavorite() ?? false;
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function toggleOwned(): void
    {
        try {
            $result = $this->userBookService->toggleOwned($this->getUser(), $this->book);
            $message = $result['newValue']
                ? 'Ajouté à votre collection'
                : 'Retiré de votre collection';
            $this->dispatchBrowserEvent('toast', ['message' => $message, 'type' => 'success']);
        } catch (\Throwable) {
            $this->dispatchBrowserEvent('toast', [
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'type'    => 'error',
            ]);
        }
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function toggleToRead(): void
    {
        try {
            $result = $this->userBookService->toggleToRead($this->getUser(), $this->book);
            $message = $result['newValue']
                ? 'Ajouté à la liste À lire'
                : 'Retiré de la liste À lire';
            $this->dispatchBrowserEvent('toast', ['message' => $message, 'type' => 'success']);
        } catch (\Throwable) {
            $this->dispatchBrowserEvent('toast', [
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'type'    => 'error',
            ]);
        }
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function toggleToBuy(): void
    {
        try {
            $result = $this->userBookService->toggleToBuy($this->getUser(), $this->book);
            $message = $result['newValue']
                ? 'Ajouté à la liste À acheter'
                : 'Retiré de la liste À acheter';
            $this->dispatchBrowserEvent('toast', ['message' => $message, 'type' => 'success']);
        } catch (\Throwable) {
            $this->dispatchBrowserEvent('toast', [
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'type'    => 'error',
            ]);
        }
    }

    #[LiveAction]
    #[IsGranted('ROLE_USER')]
    public function toggleFavorite(): void
    {
        try {
            $result = $this->userBookService->toggleFavorite($this->getUser(), $this->book);
            $message = $result['newValue']
                ? 'Ajouté à vos favoris'
                : 'Retiré de vos favoris';
            $this->dispatchBrowserEvent('toast', ['message' => $message, 'type' => 'success']);
        } catch (\Throwable) {
            $this->dispatchBrowserEvent('toast', [
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'type'    => 'error',
            ]);
        }
    }
}
