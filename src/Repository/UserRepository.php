<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findOneByEmail($identifier);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        if ($user->getDeletedAt() !== null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower($email)]);
    }

    public function findOneByGoogleId(string $googleId): ?User
    {
        return $this->findOneBy(['googleId' => $googleId]);
    }

    public function findOneByPseudo(string $pseudo): ?User
    {
        return $this->findOneBy(['pseudo' => $pseudo]);
    }

    public function isEmailTaken(string $email): bool
    {
        return $this->findOneByEmail($email) !== null;
    }

    public function isPseudoTaken(string $pseudo): bool
    {
        return $this->findOneByPseudo($pseudo) !== null;
    }

    public function countActiveAdministrators(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COUNT(*) FROM "user" WHERE status = \'active\' AND deleted_at IS NULL AND roles::jsonb @> \'["ROLE_ADMIN"]\'::jsonb';

        return (int) $conn->fetchOne($sql);
    }

    public function countAccountsWithModerationCapability(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COUNT(*) FROM "user" WHERE status = \'active\' AND deleted_at IS NULL AND (roles::jsonb @> \'["ROLE_ADMIN"]\'::jsonb OR roles::jsonb @> \'["ROLE_MODERATOR"]\'::jsonb)';

        return (int) $conn->fetchOne($sql);
    }

    /** @return User[] */
    public function findAllNonDeleted(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
