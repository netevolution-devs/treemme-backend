<?php

namespace App\Entity;

use App\Repository\BatchSelectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchSelectionRepository::class)]
class BatchSelection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list', 'batch_composition_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_selection_detail', 'batch_list'])]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list', 'batch_composition_detail'])]
    private ?Selection $selection = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $pieces = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $stock_pieces = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?float $stock_quantity = null;

    #[ORM\ManyToOne(inversedBy: 'batchSelections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?LeatherThickness $thickness = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'batch_list'])]
    private ?string $note = null;

    /**
     * @var Collection<int, BatchComposition>
     */
    #[ORM\OneToMany(mappedBy: 'selection', targetEntity: BatchComposition::class)]
    private Collection $batchCompositions;

    public function __construct()
    {
        $this->batchCompositions = new ArrayCollection();
    }

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

    public function getPieces(): ?float
    {
        return $this->pieces;
    }

    public function setPieces(?float $pieces): static
    {
        if ($pieces !== null) {
            // Movimenti selezioni devono essere SEMPRE gestiti in intere
            $pieces = (float)round($pieces);
        }
        $this->pieces = $pieces;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity !== null ? round($quantity, 3) : null;

        return $this;
    }

    public function getStockPieces(): ?float
    {
        return $this->stock_pieces;
    }

    public function setStockPieces(?float $stock_pieces): static
    {
        if ($stock_pieces !== null) {
            // Movimenti selezioni devono essere SEMPRE gestiti in intere
            $stock_pieces = (float)round($stock_pieces);
        }
        $this->stock_pieces = $stock_pieces;

        return $this;
    }

    public function getStockQuantity(): ?float
    {
        return $this->stock_quantity;
    }

    public function setStockQuantity(?float $stock_quantity): static
    {
        $this->stock_quantity = $stock_quantity !== null ? round($stock_quantity, 3) : null;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return Collection<int, BatchComposition>
     */
    public function getBatchCompositions(): Collection
    {
        return $this->batchCompositions;
    }

    public function addBatchComposition(BatchComposition $batchComposition): static
    {
        if (!$this->batchCompositions->contains($batchComposition)) {
            $this->batchCompositions->add($batchComposition);
            $batchComposition->setSelection($this);
        }

        return $this;
    }

    public function removeBatchComposition(BatchComposition $batchComposition): static
    {
        if ($this->batchCompositions->removeElement($batchComposition)) {
            // set the owning side to null (unless already changed)
            if ($batchComposition->getSelection() === $this) {
                $batchComposition->setSelection(null);
            }
        }

        return $this;
    }
}
