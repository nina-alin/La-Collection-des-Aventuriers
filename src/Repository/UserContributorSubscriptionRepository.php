<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\User;
use App\Entity\UserContributorSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserContributorSubscription>
 */
class UserContributorSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserContributorSubscription::class);
    }

    public function findByUserAndContributor(User $user, Contributor $contributor): ?UserContributorSubscription
    {
        return $this->findOneBy(['user' => $user, 'contributor' => $contributor]);
    }

    /** @return UserContributorSubscription[] */
    public function findFollowedByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['subscribedAt' => 'DESC']);
    }
}
