<?php

namespace App\Entity;

use App\Entity\Collection as CollectionEntity;
use App\Entity\Enum\BookStatus;
use App\EntityListener\BookSoftDeleteListener;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\EntityListeners([BookSoftDeleteListener::class])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['slug'], name: 'idx_book_slug')]
#[ORM\Index(columns: ['status'], name: 'idx_book_status')]
#[ORM\Index(columns: ['collection_id'], name: 'idx_book_collection_id')]
#[UniqueEntity(fields: ['isbn'], message: 'Cet ISBN est déjà enregistré.')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
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

    /** @var Collection<int, Contribution> */
    #[ORM\OneToMany(targetEntity: Contribution::class, mappedBy: 'book')]
    private Collection $contributions;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Editor::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Editor $editor = null;

    /** @var Collection<int, BookImage> */
    #[ORM\OneToMany(targetEntity: BookImage::class, mappedBy: 'book', cascade: ['remove'], orphanRemoval: true)]
    private Collection $galleryImages;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', cascade: ['remove'], orphanRemoval: true)]
    private Collection $reviews;

    #[ORM\ManyToOne(targetEntity: CollectionEntity::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CollectionEntity $collection = null;

    /** @var Collection<int, UserBook> */
    #[ORM\OneToMany(targetEntity: UserBook::class, mappedBy: 'book', cascade: ['remove'])]
    private Collection $userBooks;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $followNotificationSentAt = null;

    public function __construct()
    {
        $this->contributions = new ArrayCollection();
        $this->galleryImages = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->userBooks = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPrePersistOrUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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

    /** @return Collection<int, Contribution> */
    public function getContributions(): Collection
    {
        return $this->contributions;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getFollowNotificationSentAt(): ?\DateTimeImmutable { return $this->followNotificationSentAt; }

    public function setFollowNotificationSentAt(?\DateTimeImmutable $dt): static
    {
        $this->followNotificationSentAt = $dt;
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

    /** @return Collection<int, Review> */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /** @return Collection<int, UserBook> */
    public function getUserBooks(): Collection
    {
        return $this->userBooks;
    }
}
