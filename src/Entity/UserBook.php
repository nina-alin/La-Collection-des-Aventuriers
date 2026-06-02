<?php

namespace App\Entity;

use App\Repository\UserBookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBookRepository::class)]
#[ORM\Table(name: 'user_book')]
#[ORM\UniqueConstraint(name: 'uniq_user_book', columns: ['user_id', 'book_id'])]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_book_user_id')]
#[ORM\Index(columns: ['book_id'], name: 'idx_user_book_book_id')]
#[ORM\HasLifecycleCallbacks]
class UserBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'userBooks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOwned = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isToRead = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isToBuy = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFavorite = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Book $book)
    {
        $this->user      = $user;
        $this->book      = $book;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function isOwned(): bool
    {
        return $this->isOwned;
    }

    public function setIsOwned(bool $isOwned): static
    {
        $this->isOwned = $isOwned;
        return $this;
    }

    public function isToRead(): bool
    {
        return $this->isToRead;
    }

    public function setIsToRead(bool $isToRead): static
    {
        $this->isToRead = $isToRead;
        return $this;
    }

    public function isToBuy(): bool
    {
        return $this->isToBuy;
    }

    public function setIsToBuy(bool $isToBuy): static
    {
        $this->isToBuy = $isToBuy;
        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }

    public function isAllInactive(): bool
    {
        return !$this->isOwned && !$this->isToRead && !$this->isToBuy && !$this->isFavorite;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
