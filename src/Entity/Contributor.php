<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContributorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ContributorRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Contributor
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    private string $lastName = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $biography = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $nationality = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $deathDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $portraitImage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /** @var Collection<int, Contribution> */
    #[ORM\OneToMany(targetEntity: Contribution::class, mappedBy: 'contributor', cascade: ['remove'])]
    private Collection $contributions;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->contributions = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getDeathDate(): ?\DateTimeInterface
    {
        return $this->deathDate;
    }

    public function setDeathDate(?\DateTimeInterface $deathDate): static
    {
        $this->deathDate = $deathDate;
        return $this;
    }

    public function getPortraitImage(): ?string
    {
        return $this->portraitImage;
    }

    public function setPortraitImage(?string $portraitImage): static
    {
        $this->portraitImage = $portraitImage;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /** @return Collection<int, Contribution> */
    public function getContributions(): Collection
    {
        return $this->contributions;
    }
}
