<?php

namespace App\Service;

use App\Entity\Enum\SuggestionEntityType;
use App\Entity\Enum\SuggestionMode;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class SuggestionService
{
    private const PENDING_QUOTA = 20;

    public function __construct(
        private readonly SuggestionRepository $suggestionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function submit(
        User $user,
        array $formData,
        SuggestionEntityType $type,
        SuggestionMode $mode,
        ?string $sourceEntityId,
        ?string $coverImageTempPath,
    ): Suggestion {
        $pendingCount = $this->suggestionRepository->findPendingCountByUser($user);
        if ($pendingCount >= self::PENDING_QUOTA) {
            throw new \RuntimeException(sprintf(
                'Quota atteint : vous ne pouvez pas avoir plus de %d suggestions en attente.',
                self::PENDING_QUOTA
            ));
        }

        $suggestion = new Suggestion();
        $suggestion->setUser($user);
        $suggestion->setEntityType($type);
        $suggestion->setMode($mode);
        $suggestion->setFormData($formData);

        if ($sourceEntityId !== null) {
            $suggestion->setSourceEntityId(Uuid::fromString($sourceEntityId));
        }

        if ($coverImageTempPath !== null) {
            $suggestion->setCoverImagePath($coverImageTempPath);
        }

        $this->entityManager->persist($suggestion);
        $this->entityManager->flush();

        return $suggestion;
    }

    public function getPendingCount(User $user): int
    {
        return $this->suggestionRepository->findPendingCountByUser($user);
    }
}
