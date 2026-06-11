<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enum\UserListType;
use App\Entity\User;
use App\Entity\UserListVisibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserListVisibility>
 */
class UserListVisibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserListVisibility::class);
    }

    public function findByUserAndType(User $user, UserListType $type): ?UserListVisibility
    {
        return $this->findOneBy(['user' => $user, 'listType' => $type]);
    }

    /** @return array<string, UserListVisibility> keyed by list type string value */
    public function findAllByUser(User $user): array
    {
        $results = $this->findBy(['user' => $user]);
        $keyed = [];
        foreach ($results as $visibility) {
            $keyed[$visibility->getListType()->value] = $visibility;
        }
        return $keyed;
    }
}
