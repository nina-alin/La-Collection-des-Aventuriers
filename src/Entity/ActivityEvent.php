<?php

namespace App\Entity;

use App\Entity\Enum\ActivityEventType;
use App\Repository\ActivityEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityEventRepository::class)]
#[ORM\Table(name: 'activity_event')]
#[ORM\Index(columns: ['created_at'], name: 'idx_activity_event_created_at')]
#[ORM\Index(columns: ['type', 'created_at'], name: 'idx_activity_event_type_created_at')]
class ActivityEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: ActivityEventType::class)]
    private ActivityEventType $type;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $actorUser;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $actorInitials = null;

    #[ORM\Column(length: 30)]
    private string $actorPseudo;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bookTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bookSlug = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $statusBadge = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ActivityEventType { return $this->type; }
    public function setType(ActivityEventType $type): static { $this->type = $type; return $this; }

    public function getActorUser(): User { return $this->actorUser; }
    public function setActorUser(User $actorUser): static { $this->actorUser = $actorUser; return $this; }

    public function getActorInitials(): ?string { return $this->actorInitials; }
    public function setActorInitials(?string $actorInitials): static { $this->actorInitials = $actorInitials; return $this; }

    public function getActorPseudo(): string { return $this->actorPseudo; }
    public function setActorPseudo(string $actorPseudo): static { $this->actorPseudo = $actorPseudo; return $this; }

    public function getBookTitle(): ?string { return $this->bookTitle; }
    public function setBookTitle(?string $bookTitle): static { $this->bookTitle = $bookTitle; return $this; }

    public function getBookSlug(): ?string { return $this->bookSlug; }
    public function setBookSlug(?string $bookSlug): static { $this->bookSlug = $bookSlug; return $this; }

    public function getStatusBadge(): ?string { return $this->statusBadge; }
    public function setStatusBadge(?string $statusBadge): static { $this->statusBadge = $statusBadge; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
