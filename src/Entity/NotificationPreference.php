<?php

namespace App\Entity;

use App\Entity\Enum\NotificationType;
use App\Repository\NotificationPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationPreferenceRepository::class)]
#[ORM\Table(name: 'notification_preference')]
class NotificationPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(options: ['default' => true])]
    private bool $contributionValidated = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $contributionRefused = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $bookActivity = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $moderationPending = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $rankUp = true;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }

    public function isEnabled(NotificationType $type): bool
    {
        return match ($type) {
            NotificationType::CONTRIBUTION_VALIDATED => $this->contributionValidated,
            NotificationType::CONTRIBUTION_REFUSED   => $this->contributionRefused,
            NotificationType::BOOK_ACTIVITY          => $this->bookActivity,
            NotificationType::MODERATION_PENDING     => $this->moderationPending,
            NotificationType::RANK_UP                => $this->rankUp,
        };
    }

    public function isContributionValidated(): bool { return $this->contributionValidated; }
    public function setContributionValidated(bool $v): static { $this->contributionValidated = $v; return $this; }

    public function isContributionRefused(): bool { return $this->contributionRefused; }
    public function setContributionRefused(bool $v): static { $this->contributionRefused = $v; return $this; }

    public function isBookActivity(): bool { return $this->bookActivity; }
    public function setBookActivity(bool $v): static { $this->bookActivity = $v; return $this; }

    public function isModerationPending(): bool { return $this->moderationPending; }
    public function setModerationPending(bool $v): static { $this->moderationPending = $v; return $this; }

    public function isRankUp(): bool { return $this->rankUp; }
    public function setRankUp(bool $v): static { $this->rankUp = $v; return $this; }
}
