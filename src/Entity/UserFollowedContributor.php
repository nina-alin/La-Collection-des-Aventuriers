<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserFollowedContributorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFollowedContributorRepository::class)]
#[ORM\Table(name: 'user_followed_contributor')]
#[ORM\UniqueConstraint(name: 'uniq_user_followed_contributor', columns: ['user_id', 'contributor_id'])]
#[ORM\Index(columns: ['contributor_id'], name: 'idx_user_followed_contributor_contrib')]
class UserFollowedContributor
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
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Contributor $contributor)
    {
        $this->user        = $user;
        $this->contributor = $contributor;
        $this->createdAt   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getContributor(): Contributor { return $this->contributor; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
