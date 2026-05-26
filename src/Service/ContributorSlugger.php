<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class ContributorSlugger
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly ContributorRepository $repo,
    ) {}

    public function generateUnique(Contributor $contributor): string
    {
        $input = $contributor->getPseudo() ?? ($contributor->getFirstName() . ' ' . $contributor->getLastName());
        $base = $this->slugger->slug($input)->lower()->toString();

        if ($this->repo->findOneBy(['slug' => $base]) === null) {
            return $base;
        }

        $i = 2;
        do {
            $candidate = $base . '-' . $i++;
        } while ($this->repo->findOneBy(['slug' => $candidate]) !== null);

        return $candidate;
    }
}
