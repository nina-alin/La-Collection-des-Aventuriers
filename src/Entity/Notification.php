<?php

namespace App\Entity;

use App\Entity\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\UniqueConstraint(name: 'uniq_notification_user_source', columns: ['user_id', 'source_id'])]
#[ORM\Index(columns: ['user_id', 'is_read', 'created_at'], name: 'idx_notification_user_read_date')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_notification_user_date')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 50, enumType: NotificationType::class)]
    private NotificationType $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $targetUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255)]
    private string $sourceId;

    public function __construct(User $user, NotificationType $type, string $message, string $sourceId, ?string $targetUrl = null)
    {
        $this->user      = $user;
        $this->type      = $type;
        $this->message   = $message;
        $this->sourceId  = $sourceId;
        $this->targetUrl = $targetUrl;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getType(): NotificationType { return $this->type; }
    public function getMessage(): string { return $this->message; }
    public function getTargetUrl(): ?string { return $this->targetUrl; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getSourceId(): string { return $this->sourceId; }

    public function markRead(): void { $this->isRead = true; }
}
