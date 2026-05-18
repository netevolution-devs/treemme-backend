<?php

namespace App\Entity;

use App\Repository\BatchCompositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchCompositionRepository::class)]
class BatchComposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchCompositions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'sonBatches')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?Batch $father_batch = null;

    #[ORM\Column]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?int $father_batch_piece = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?float $father_batch_quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?string $composition_note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'batchCompositions')]
    private ?BatchSelection $selection = null;

    #[ORM\ManyToOne(inversedBy: 'batchCompositions')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['batch_composition_list', 'batch_composition_detail', 'batch_detail'])]
    private ?LeatherThickness $thickness = null;

    #[ORM\Column(nullable: true)]
    private ?int $father_batch_piece_available = null;

    #[ORM\Column(nullable: true)]
    private ?float $father_batch_quantity_available = null;

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
        $this->father_batch_quantity = $father_batch_quantity !== null ? round($father_batch_quantity, 3) : null;

        return $this;
    }

    public function getCompositionNote(): ?string
    {
        return $this->composition_note;
    }

    public function setCompositionNote(?string $composition_note): static
    {
        $this->composition_note = $composition_note;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getSelection(): ?BatchSelection
    {
        return $this->selection;
    }

    public function setSelection(?BatchSelection $selection): static
    {
        $this->selection = $selection;

        return $this;
    }

    public function getThickness(): ?LeatherThickness
    {
        return $this->thickness;
    }

    public function setThickness(?LeatherThickness $thickness): static
    {
        $this->thickness = $thickness;

        return $this;
    }

    public function getFatherBatchPieceAvailable(): ?int
    {
        return $this->father_batch_piece_available;
    }

    public function setFatherBatchPieceAvailable(?int $father_batch_piece_available): static
    {
        $this->father_batch_piece_available = $father_batch_piece_available;

        return $this;
    }

    public function getFatherBatchQuantityAvailable(): ?float
    {
        return $this->father_batch_quantity_available;
    }

    public function setFatherBatchQuantityAvailable(?float $father_batch_quantity_available): static
    {
        $this->father_batch_quantity_available = $father_batch_quantity_available;

        return $this;
    }
}
