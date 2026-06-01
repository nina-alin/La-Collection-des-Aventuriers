<?php

namespace App\Entity;

use App\Entity\Enum\UserBookStatus;
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

    #[ORM\Column(length: 30, enumType: UserBookStatus::class)]
    private UserBookStatus $status = UserBookStatus::DANS_MA_COLLECTION;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFavorite = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Book $book, UserBookStatus $status = UserBookStatus::DANS_MA_COLLECTION)
    {
        $this->user      = $user;
        $this->book      = $book;
        $this->status    = $status;
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

    public function getStatus(): UserBookStatus
    {
        return $this->status;
    }

    public function setStatus(UserBookStatus $status): static
    {
        $this->status = $status;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
