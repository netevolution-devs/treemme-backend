<?php

namespace App\Entity;

use App\Repository\BatchSelectionRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchSelectionRepository::class)]
class BatchSelection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_selection_detail', 'batch_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_selection_detail'])]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?Selection $selection = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?int $pieces = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?int $stock_pieces = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $stock_quantity = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LeatherThickness $thickness = null;

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

    public function getSelection(): ?Selection
    {
        return $this->selection;
    }

    public function setSelection(?Selection $selection): static
    {
        $this->selection = $selection;

        return $this;
    }

    public function getPieces(): ?int
    {
        return $this->pieces;
    }

    public function setPieces(?int $pieces): static
    {
        $this->pieces = $pieces;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getStockPieces(): ?int
    {
        return $this->stock_pieces;
    }

    public function setStockPieces(?int $stock_pieces): static
    {
        $this->stock_pieces = $stock_pieces;

        return $this;
    }

    public function getStockQuantity(): ?float
    {
        return $this->stock_quantity;
    }

    public function setStockQuantity(?float $stock_quantity): static
    {
        $this->stock_quantity = $stock_quantity;

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
}
