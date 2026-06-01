<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CollectionPublishingHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollectionPublishingHistoryRepository::class)]
#[ORM\Table(name: 'collection_publishing_history')]
#[ORM\Index(columns: ['collection_id', 'start_year', 'id'], name: 'idx_cph_collection_year_id')]
class CollectionPublishingHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Collection::class, inversedBy: 'publishingHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Collection $collection;

    #[ORM\ManyToOne(targetEntity: Editor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Editor $editor = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1800, max: 2100)]
    private int $startYear;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1800, max: 2100)]
    private ?int $endYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editionName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection): static
    {
        $this->collection = $collection;
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

    public function getStartYear(): int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;
        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(?int $endYear): static
    {
        $this->endYear = $endYear;
        return $this;
    }

    public function getEditionName(): ?string
    {
        return $this->editionName;
    }

    public function setEditionName(?string $editionName): static
    {
        $this->editionName = $editionName;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }
}
