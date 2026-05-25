<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CollectionRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class CollectionSlugger
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly CollectionRepository $repo,
    ) {}

    public function generateUnique(string $nom, ?string $currentSlug = null): string
    {
        $base = $this->slugger->slug($nom)->lower()->toString();
        if ($currentSlug === $base || $this->repo->findOneBy(['slug' => $base]) === null) {
            return $base;
        }
        $i = 2;
        do {
            $candidate = $base . '-' . $i++;
        } while ($this->repo->findOneBy(['slug' => $candidate]) !== null);
        return $candidate;
    }
}
