<?php

namespace App\Entity;

use App\Entity\Enum\BookImageTab;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_book_tab', columns: ['book_id', 'tab'])]
class BookImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: BookImageTab::class)]
    private BookImageTab $tab;

    #[ORM\Column(length: 255)]
    private string $imagePath = '';

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'galleryImages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTab(): BookImageTab
    {
        return $this->tab;
    }

    public function setTab(BookImageTab $tab): static
    {
        $this->tab = $tab;
        return $this;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;
        return $this;
    }
}
