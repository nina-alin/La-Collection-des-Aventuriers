<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'moderation_log')]
#[ORM\HasLifecycleCallbacks]
class ModerationLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $moderatorId;

    #[ORM\Column(length: 10)]
    private string $actionType;

    #[ORM\Column(length: 100)]
    private string $targetEntityType;

    #[ORM\Column(length: 36)]
    private string $targetEntityId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ?string $moderatorId,
        string $actionType,
        string $targetEntityType,
        string $targetEntityId,
        ?string $reason = null,
    ) {
        $this->id = Uuid::v4();
        $this->moderatorId = $moderatorId;
        $this->actionType = $actionType;
        $this->targetEntityType = $targetEntityType;
        $this->targetEntityId = $targetEntityId;
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): never
    {
        throw new \LogicException('ModerationLog is append-only');
    }

    #[ORM\PreRemove]
    public function onPreRemove(): never
    {
        throw new \LogicException('ModerationLog is append-only');
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getModeratorId(): ?string
    {
        return $this->moderatorId;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getTargetEntityType(): string
    {
        return $this->targetEntityType;
    }

    public function getTargetEntityId(): string
    {
        return $this->targetEntityId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
