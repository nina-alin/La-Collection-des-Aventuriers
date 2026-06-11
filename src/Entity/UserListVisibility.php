<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\UserListType;
use App\Repository\UserListVisibilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserListVisibilityRepository::class)]
#[ORM\Table(name: 'user_list_visibility')]
#[ORM\UniqueConstraint(name: 'uniq_user_list_visibility', columns: ['user_id', 'list_type'])]
class UserListVisibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', enumType: UserListType::class, length: 20)]
    private UserListType $listType;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublic = false;

    public function __construct(User $user, UserListType $listType, bool $isPublic = false)
    {
        $this->user     = $user;
        $this->listType = $listType;
        $this->isPublic = $isPublic;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getListType(): UserListType { return $this->listType; }
    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): static { $this->isPublic = $isPublic; return $this; }
}
