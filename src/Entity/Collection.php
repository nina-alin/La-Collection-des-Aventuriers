<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\GenreCollection;
use App\Entity\Enum\StatutCollection;
use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(name: 'collection')]
#[ORM\UniqueConstraint(name: 'uniq_collection_slug', columns: ['slug'])]
#[ORM\UniqueConstraint(name: 'uniq_collection_nom', columns: ['nom'])]
#[UniqueEntity(fields: ['nom'], message: 'Ce nom de collection est déjà utilisé.')]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé.')]
class Collection
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $nom = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $nomOriginal = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $description = '';

    #[ORM\Column(length: 50, enumType: GenreCollection::class)]
    private GenreCollection $genre = GenreCollection::AVENTURE;

    #[ORM\Column(type: 'json')]
    private array $createurs = [];

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(2026)]
    private ?int $anneeCreation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $editeurHistorique = null;

    #[ORM\Column(length: 20, enumType: StatutCollection::class)]
    private StatutCollection $statut = StatutCollection::EN_COURS;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $imageLogo = null;

    /** @var DoctrineCollection<int, Book> */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'collection')]
    private DoctrineCollection $books;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->books = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(?string $nomOriginal): static
    {
        $this->nomOriginal = $nomOriginal;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getGenre(): GenreCollection
    {
        return $this->genre;
    }

    public function setGenre(GenreCollection $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getCreateurs(): array
    {
        return $this->createurs;
    }

    public function setCreateurs(array $createurs): static
    {
        $this->createurs = $createurs;
        return $this;
    }

    public function getAnneeCreation(): ?int
    {
        return $this->anneeCreation;
    }

    public function setAnneeCreation(?int $anneeCreation): static
    {
        $this->anneeCreation = $anneeCreation;
        return $this;
    }

    public function getEditeurHistorique(): ?string
    {
        return $this->editeurHistorique;
    }

    public function setEditeurHistorique(?string $editeurHistorique): static
    {
        $this->editeurHistorique = $editeurHistorique;
        return $this;
    }

    public function getStatut(): StatutCollection
    {
        return $this->statut;
    }

    public function setStatut(StatutCollection $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getImageLogo(): ?string
    {
        return $this->imageLogo;
    }

    public function setImageLogo(?string $imageLogo): static
    {
        $this->imageLogo = $imageLogo;
        return $this;
    }

    /** @return DoctrineCollection<int, Book> */
    public function getBooks(): DoctrineCollection
    {
        return $this->books;
    }
}
