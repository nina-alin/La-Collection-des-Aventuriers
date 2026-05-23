<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class GoogleOAuth2Service
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserRegistrationService $registrationService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findOrCreateUser(array $googleUserInfo): User
    {
        if (!($googleUserInfo['email_verified'] ?? false)) {
            throw new \RuntimeException('Adresse Google non vérifiée. Utilisez la connexion classique.');
        }

        $email = strtolower($googleUserInfo['email']);
        $existingUser = $this->userRepository->findOneByEmail($email);

        if ($existingUser !== null) {
            return $existingUser;
        }

        $pseudo = $this->generateUniquePseudo(
            $googleUserInfo['name'] ?? '',
            substr($email, 0, (int) strpos($email, '@')) ?: 'user',
        );

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setGoogleId($googleUserInfo['sub'] ?? null);
        $user->setDisplayName($googleUserInfo['name'] ?? null);
        $user->setAvatarUrl($googleUserInfo['picture'] ?? null);
        $user->setRoles([]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function generateUniquePseudo(string $googleName, string $emailLocalPart): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $googleName);

        if ($base === '' || $base === null) {
            $base = preg_replace('/[^a-zA-Z0-9_]/', '', $emailLocalPart);
        }

        if ($base === '' || $base === null) {
            $base = 'user';
        }

        $base = substr($base, 0, 30);

        $candidate = $base;
        $suffix = 2;

        while ($this->userRepository->isPseudoTaken($candidate)) {
            $suffixStr = '_'.$suffix;
            $candidate = substr($base, 0, 30 - strlen($suffixStr)).$suffixStr;
            $suffix++;
        }

        return $candidate;
    }
}
