<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Enum\ContributionRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contributor>
 */
class ContributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contributor::class);
    }

    public function findBySlugAndRole(string $slug, ContributionRole $role): ?Contributor
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT c, contrib, b
                 FROM App\Entity\Contributor c
                 INNER JOIN c.contributions contrib
                 INNER JOIN contrib.book b
                 WHERE c.slug = :slug
                   AND contrib.role = :role
                 ORDER BY CASE WHEN b.frenchPublicationYear IS NULL THEN 9999 ELSE b.frenchPublicationYear END ASC,
                          b.title ASC'
            )
            ->setParameter('slug', $slug)
            ->setParameter('role', $role->value)
            ->getOneOrNullResult();
    }
}
