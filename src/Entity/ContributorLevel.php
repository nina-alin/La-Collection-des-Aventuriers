<?php

namespace App\Entity;

use App\Repository\ContributorLevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContributorLevelRepository::class)]
class ContributorLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'smallint', unique: true)]
    private int $rankNumber;

    #[ORM\Column]
    private int $threshold;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRankNumber(): int
    {
        return $this->rankNumber;
    }

    public function setRankNumber(int $rankNumber): static
    {
        $this->rankNumber = $rankNumber;
        return $this;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): static
    {
        $this->threshold = $threshold;
        return $this;
    }
}
