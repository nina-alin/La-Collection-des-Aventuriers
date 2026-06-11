<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserContributorSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserContributorSubscriptionRepository::class)]
#[ORM\Table(name: 'user_contributor_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_user_contributor_sub', columns: ['user_id', 'contributor_id'])]
class UserContributorSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Contributor $contributor;

    #[ORM\Column]
    private \DateTimeImmutable $subscribedAt;

    public function __construct(User $user, Contributor $contributor)
    {
        $this->user        = $user;
        $this->contributor = $contributor;
        $this->subscribedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getContributor(): Contributor { return $this->contributor; }
    public function getSubscribedAt(): \DateTimeImmutable { return $this->subscribedAt; }
}
