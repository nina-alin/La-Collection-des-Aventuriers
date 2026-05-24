<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CorrectionProposalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CorrectionProposalRepository::class)]
#[ORM\Table(name: 'correction_proposal')]
class CorrectionProposal
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkEntry::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WorkEntry $workEntry;

    #[ORM\Column(type: 'json')]
    private array $proposedContent;

    #[ORM\Column(length: 10, options: ['default' => 'PENDING'])]
    private string $status = 'PENDING';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(WorkEntry $workEntry, array $proposedContent, ?User $author = null)
    {
        $this->id = Uuid::v4();
        $this->workEntry = $workEntry;
        $this->proposedContent = $proposedContent;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getWorkEntry(): WorkEntry
    {
        return $this->workEntry;
    }

    public function getProposedContent(): array
    {
        return $this->proposedContent;
    }

    public function setProposedContent(array $proposedContent): static
    {
        $this->proposedContent = $proposedContent;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
