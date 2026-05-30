<?php

namespace App\Entity;

use App\Repository\SuggestionRefusalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SuggestionRefusalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SuggestionRefusal
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: Suggestion::class, inversedBy: 'refusal')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Suggestion $suggestion;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $moderator = null;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'json')]
    private array $actions = [];

    #[ORM\Column]
    private \DateTimeImmutable $refusedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->refusedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSuggestion(): Suggestion
    {
        return $this->suggestion;
    }

    public function setSuggestion(Suggestion $suggestion): static
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): static
    {
        $this->moderator = $moderator;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    public function getRefusedAt(): \DateTimeImmutable
    {
        return $this->refusedAt;
    }
}
