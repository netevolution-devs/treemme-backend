<?php

namespace App\Entity;

use App\Repository\BatchCompositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchCompositionRepository::class)]
class BatchComposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchCompositions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'sonBatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $father_batch = null;

    #[ORM\Column]
    private ?int $father_batch_piece = null;

    #[ORM\Column(nullable: true)]
    private ?float $father_batch_quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $composition_note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getFatherBatch(): ?Batch
    {
        return $this->father_batch;
    }

    public function setFatherBatch(?Batch $father_batch): static
    {
        $this->father_batch = $father_batch;

        return $this;
    }

    public function getFatherBatchPiece(): ?int
    {
        return $this->father_batch_piece;
    }

    public function setFatherBatchPiece(int $father_batch_piece): static
    {
        $this->father_batch_piece = $father_batch_piece;

        return $this;
    }

    public function getFatherBatchQuantity(): ?float
    {
        return $this->father_batch_quantity;
    }

    public function setFatherBatchQuantity(?float $father_batch_quantity): static
    {
        $this->father_batch_quantity = $father_batch_quantity;

        return $this;
    }

    public function getCompositionNote(): ?string
    {
        return $this->composition_note;
    }

    public function setCompositionNote(string $composition_note): static
    {
        $this->composition_note = $composition_note;

        return $this;
    }
}
