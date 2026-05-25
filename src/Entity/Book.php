<?php

namespace App\Entity;

use App\Entity\Collection as CollectionEntity;
use App\Entity\Enum\BookStatus;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Index(columns: ['slug'], name: 'idx_book_slug')]
#[ORM\Index(columns: ['status'], name: 'idx_book_status')]
#[ORM\Index(columns: ['collection_id'], name: 'idx_book_collection_id')]
#[UniqueEntity(fields: ['isbn'], message: 'Cet ISBN est déjà enregistré.')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalTitle = null;

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 20, nullable: true, unique: true)]
    private ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    private ?int $pages = null;

    #[ORM\Column(nullable: true)]
    private ?int $paragraphs = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $frenchPublicationYear = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $originalPublicationYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editionInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $saga = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $volumeNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $taverneUrl = null;

    #[ORM\Column(length: 20, enumType: BookStatus::class)]
    private BookStatus $status = BookStatus::PENDING;

    #[ORM\Column(type: 'json')]
    private array $languages = [];

    /** @var Collection<int, Author> */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_author')]
    private Collection $authors;

    /** @var Collection<int, Illustrator> */
    #[ORM\ManyToMany(targetEntity: Illustrator::class, inversedBy: 'books')]
    #[ORM\JoinTable(name: 'book_illustrator')]
    private Collection $illustrators;

    #[ORM\ManyToOne(targetEntity: Translator::class, inversedBy: 'books')]
    private ?Translator $translator = null;

    #[ORM\ManyToOne(targetEntity: Editor::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Editor $editor = null;

    /** @var Collection<int, BookImage> */
    #[ORM\OneToMany(targetEntity: BookImage::class, mappedBy: 'book', cascade: ['remove'], orphanRemoval: true)]
    private Collection $galleryImages;

    #[ORM\ManyToOne(targetEntity: CollectionEntity::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CollectionEntity $collection = null;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->illustrators = new ArrayCollection();
        $this->galleryImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getOriginalTitle(): ?string
    {
        return $this->originalTitle;
    }

    public function setOriginalTitle(?string $originalTitle): static
    {
        $this->originalTitle = $originalTitle;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    public function setPages(?int $pages): static
    {
        $this->pages = $pages;
        return $this;
    }

    public function getParagraphs(): ?int
    {
        return $this->paragraphs;
    }

    public function setParagraphs(?int $paragraphs): static
    {
        $this->paragraphs = $paragraphs;
        return $this;
    }

    public function getFrenchPublicationYear(): ?int
    {
        return $this->frenchPublicationYear;
    }

    public function setFrenchPublicationYear(?int $year): static
    {
        $this->frenchPublicationYear = $year;
        return $this;
    }

    public function getOriginalPublicationYear(): ?int
    {
        return $this->originalPublicationYear;
    }

    public function setOriginalPublicationYear(?int $year): static
    {
        $this->originalPublicationYear = $year;
        return $this;
    }

    public function getEditionInfo(): ?string
    {
        return $this->editionInfo;
    }

    public function setEditionInfo(?string $editionInfo): static
    {
        $this->editionInfo = $editionInfo;
        return $this;
    }

    public function getSaga(): ?string
    {
        return $this->saga;
    }

    public function setSaga(?string $saga): static
    {
        $this->saga = $saga;
        return $this;
    }

    public function getVolumeNumber(): ?int
    {
        return $this->volumeNumber;
    }

    public function setVolumeNumber(?int $volumeNumber): static
    {
        $this->volumeNumber = $volumeNumber;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getTaverneUrl(): ?string
    {
        return $this->taverneUrl;
    }

    public function setTaverneUrl(?string $taverneUrl): static
    {
        $this->taverneUrl = $taverneUrl;
        return $this;
    }

    public function getStatus(): BookStatus
    {
        return $this->status;
    }

    public function setStatus(BookStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setLanguages(array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    /** @return Collection<int, Author> */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
        }
        return $this;
    }

    public function removeAuthor(Author $author): static
    {
        $this->authors->removeElement($author);
        return $this;
    }

    /** @return Collection<int, Illustrator> */
    public function getIllustrators(): Collection
    {
        return $this->illustrators;
    }

    public function addIllustrator(Illustrator $illustrator): static
    {
        if (!$this->illustrators->contains($illustrator)) {
            $this->illustrators->add($illustrator);
        }
        return $this;
    }

    public function removeIllustrator(Illustrator $illustrator): static
    {
        $this->illustrators->removeElement($illustrator);
        return $this;
    }

    public function getTranslator(): ?Translator
    {
        return $this->translator;
    }

    public function setTranslator(?Translator $translator): static
    {
        $this->translator = $translator;
        return $this;
    }

    public function getEditor(): ?Editor
    {
        return $this->editor;
    }

    public function setEditor(?Editor $editor): static
    {
        $this->editor = $editor;
        return $this;
    }

    /** @return Collection<int, BookImage> */
    public function getGalleryImages(): Collection
    {
        return $this->galleryImages;
    }

    public function addGalleryImage(BookImage $bookImage): static
    {
        if (!$this->galleryImages->contains($bookImage)) {
            $this->galleryImages->add($bookImage);
            $bookImage->setBook($this);
        }
        return $this;
    }

    public function removeGalleryImage(BookImage $bookImage): static
    {
        if ($this->galleryImages->removeElement($bookImage)) {
            if ($bookImage->getBook() === $this) {
                $bookImage->setBook($this);
            }
        }
        return $this;
    }

    public function getCollection(): ?CollectionEntity
    {
        return $this->collection;
    }

    public function setCollection(?CollectionEntity $collection): static
    {
        $this->collection = $collection;
        return $this;
    }
}
