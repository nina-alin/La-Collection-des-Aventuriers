<?php

namespace App\Entity;

use App\Repository\UserCollectionSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCollectionSubscriptionRepository::class)]
#[ORM\Table(name: 'user_collection_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_user_collection_sub', columns: ['user_id', 'collection_id'])]
#[ORM\Index(columns: ['collection_id'], name: 'idx_collection_sub_collection')]
class UserCollectionSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Collection::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Collection $collection;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Collection $collection)
    {
        $this->user       = $user;
        $this->collection = $collection;
        $this->createdAt  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getCollection(): Collection { return $this->collection; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
