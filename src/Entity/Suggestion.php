<?php

namespace App\Entity;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Enum\SuggestionStatus;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SuggestionRepository::class)]
#[ORM\Index(columns: ['user_id', 'status'], name: 'idx_suggestion_user_status')]
#[ORM\HasLifecycleCallbacks]
class Suggestion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', enumType: SuggestionEntityType::class)]
    private SuggestionEntityType $entityType;

    #[ORM\Column(type: 'string', enumType: SuggestionMode::class)]
    private SuggestionMode $mode;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $sourceEntityId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceEntityType = null;

    #[ORM\Column(type: 'json')]
    private array $formData = [];

    #[ORM\Column(type: 'string', enumType: SuggestionStatus::class, options: ['default' => 'PENDING'])]
    private SuggestionStatus $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImagePath = null;

    #[ORM\Column]
    private \DateTimeImmutable $submittedAt;

    #[ORM\OneToOne(targetEntity: SuggestionRefusal::class, mappedBy: 'suggestion', cascade: ['persist', 'remove'])]
    private ?SuggestionRefusal $refusal = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->status = SuggestionStatus::PENDING;
        $this->submittedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEntityType(): SuggestionEntityType
    {
        return $this->entityType;
    }

    public function setEntityType(SuggestionEntityType $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getMode(): SuggestionMode
    {
        return $this->mode;
    }

    public function setMode(SuggestionMode $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getSourceEntityId(): ?Uuid
    {
        return $this->sourceEntityId;
    }

    public function setSourceEntityId(?Uuid $sourceEntityId): static
    {
        $this->sourceEntityId = $sourceEntityId;
        return $this;
    }

    public function getSourceEntityType(): ?string
    {
        return $this->sourceEntityType;
    }

    public function setSourceEntityType(?string $sourceEntityType): static
    {
        $this->sourceEntityType = $sourceEntityType;
        return $this;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(array $formData): static
    {
        $this->formData = $formData;
        return $this;
    }

    public function getStatus(): SuggestionStatus
    {
        return $this->status;
    }

    public function setStatus(SuggestionStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCoverImagePath(): ?string
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(?string $coverImagePath): static
    {
        $this->coverImagePath = $coverImagePath;
        return $this;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getRefusal(): ?SuggestionRefusal
    {
        return $this->refusal;
    }

    public function setRefusal(?SuggestionRefusal $refusal): static
    {
        $this->refusal = $refusal;
        return $this;
    }
}
