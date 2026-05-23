<?php

namespace App\Service;

use App\Entity\User;
use App\EventSubscriber\AuthenticationEventSubscriber;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
        private readonly AuthenticationEventSubscriber $subscriber,
    ) {
    }

    public function register(string $pseudo, string $email, string $plainPassword): User
    {
        $email = strtolower($email);

        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser !== null) {
            if ($existingUser->getPassword() === null) {
                $this->fuseGoogleAccount($existingUser, $plainPassword);
                return $existingUser;
            }
            throw new \RuntimeException('Cette adresse email est déjà associée à un compte.');
        }

        if ($this->userRepository->isPseudoTaken($pseudo)) {
            throw new \RuntimeException('Ce pseudo n\'est pas disponible.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles([]);

        $this->em->persist($user);
        $this->em->flush();

        $this->subscriber->logAccountCreation($user);

        return $user;
    }

    public function fuseGoogleAccount(User $existing, string $plainPassword): void
    {
        $existing->setPassword($this->passwordHasher->hashPassword($existing, $plainPassword));
        $this->em->flush();
    }
}
