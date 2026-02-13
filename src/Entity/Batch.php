<?php

namespace App\Entity;

use App\Repository\BatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail', 'batch_type_detail', 'measurement_unit_detail', 'user_detail'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?bool $completed = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?bool $checked = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?BatchType $batch_type = null;

    #[ORM\Column(length: 50)]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?string $batch_code = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?\DateTime $batch_date = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?int $pieces = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?float $quantity = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?float $stock_items = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?float $storage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['batch_detail'])]
    private ?string $selection_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['batch_detail'])]
    private ?string $batch_note = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?bool $sampling = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?bool $split_selected = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?float $sq_ft_avarage_expected = null;

    #[ORM\Column]
    #[Groups(['batch_list', 'batch_detail'])]
    private ?float $sq_ft_avarage_found = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_detail'])]
    private ?\DateTime $check_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['batch_detail'])]
    private ?string $check_note = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    #[Groups(['batch_detail'])]
    private ?User $check_user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, BatchCost>
     */
    #[ORM\OneToMany(mappedBy: 'batch', targetEntity: BatchCost::class, orphanRemoval: true)]
    private Collection $batchCosts;

    /**
     * @var Collection<int, BatchComposition>
     */
    #[ORM\OneToMany(mappedBy: 'batch', targetEntity: BatchComposition::class, orphanRemoval: true)]
    private Collection $batchCompositions;

    /**
     * @var Collection<int, BatchComposition>
     */
    #[ORM\OneToMany(mappedBy: 'father_batch', targetEntity: BatchComposition::class)]
    private Collection $sonBatches;

    public function __construct()
    {
        $this->batchCosts = new ArrayCollection();
        $this->batchCompositions = new ArrayCollection();
        $this->sonBatches = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function getBatchType(): ?BatchType
    {
        return $this->batch_type;
    }

    public function setBatchType(?BatchType $batch_type): static
    {
        $this->batch_type = $batch_type;

        return $this;
    }

    public function getBatchCode(): ?string
    {
        return $this->batch_code;
    }

    public function setBatchCode(string $batch_code): static
    {
        $this->batch_code = $batch_code;

        return $this;
    }

    public function getBatchDate(): ?\DateTime
    {
        return $this->batch_date;
    }

    public function setBatchDate(?\DateTime $batch_date): static
    {
        $this->batch_date = $batch_date;

        return $this;
    }

    public function getPieces(): ?int
    {
        return $this->pieces;
    }

    public function setPieces(int $pieces): static
    {
        $this->pieces = $pieces;

        return $this;
    }

    public function getMeasurementUnit(): ?MeasurementUnit
    {
        return $this->measurement_unit;
    }

    public function setMeasurementUnit(?MeasurementUnit $measurement_unit): static
    {
        $this->measurement_unit = $measurement_unit;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getStockItems(): ?float
    {
        return $this->stock_items;
    }

    public function setStockItems(float $stock_items): static
    {
        $this->stock_items = $stock_items;

        return $this;
    }

    public function getStorage(): ?float
    {
        return $this->storage;
    }

    public function setStorage(float $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getSelectionNote(): ?string
    {
        return $this->selection_note;
    }

    public function setSelectionNote(?string $selection_note): static
    {
        $this->selection_note = $selection_note;

        return $this;
    }

    public function getBatchNote(): ?string
    {
        return $this->batch_note;
    }

    public function setBatchNote(?string $batch_note): static
    {
        $this->batch_note = $batch_note;

        return $this;
    }

    public function isSampling(): ?bool
    {
        return $this->sampling;
    }

    public function setSampling(bool $sampling): static
    {
        $this->sampling = $sampling;

        return $this;
    }

    public function isSplitSelected(): ?bool
    {
        return $this->split_selected;
    }

    public function setSplitSelected(bool $split_selected): static
    {
        $this->split_selected = $split_selected;

        return $this;
    }

    public function getSqFtAvarageExpected(): ?float
    {
        return $this->sq_ft_avarage_expected;
    }

    public function setSqFtAvarageExpected(float $sq_ft_avarage_expected): static
    {
        $this->sq_ft_avarage_expected = $sq_ft_avarage_expected;

        return $this;
    }

    public function getSqFtAvarageFound(): ?float
    {
        return $this->sq_ft_avarage_found;
    }

    public function setSqFtAvarageFound(float $sq_ft_avarage_found): static
    {
        $this->sq_ft_avarage_found = $sq_ft_avarage_found;

        return $this;
    }

    public function getCheckDate(): ?\DateTime
    {
        return $this->check_date;
    }

    public function setCheckDate(?\DateTime $check_date): static
    {
        $this->check_date = $check_date;

        return $this;
    }

    public function getCheckNote(): ?string
    {
        return $this->check_note;
    }

    public function setCheckNote(?string $check_note): static
    {
        $this->check_note = $check_note;

        return $this;
    }

    public function getCheckUser(): ?User
    {
        return $this->check_user;
    }

    public function setCheckUser(?User $check_user): static
    {
        $this->check_user = $check_user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, BatchCost>
     */
    public function getBatchCosts(): Collection
    {
        return $this->batchCosts;
    }

    public function addBatchCost(BatchCost $batchCost): static
    {
        if (!$this->batchCosts->contains($batchCost)) {
            $this->batchCosts->add($batchCost);
            $batchCost->setBatch($this);
        }

        return $this;
    }

    public function removeBatchCost(BatchCost $batchCost): static
    {
        if ($this->batchCosts->removeElement($batchCost)) {
            // set the owning side to null (unless already changed)
            if ($batchCost->getBatch() === $this) {
                $batchCost->setBatch(null);
            }
        }

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
            $batchComposition->setBatch($this);
        }

        return $this;
    }

    public function removeBatchComposition(BatchComposition $batchComposition): static
    {
        if ($this->batchCompositions->removeElement($batchComposition)) {
            // set the owning side to null (unless already changed)
            if ($batchComposition->getBatch() === $this) {
                $batchComposition->setBatch(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BatchComposition>
     */
    public function getSonBatches(): Collection
    {
        return $this->sonBatches;
    }

    public function addSonBatch(BatchComposition $sonBatch): static
    {
        if (!$this->sonBatches->contains($sonBatch)) {
            $this->sonBatches->add($sonBatch);
            $sonBatch->setFatherBatch($this);
        }

        return $this;
    }

    public function removeSonBatch(BatchComposition $sonBatch): static
    {
        if ($this->sonBatches->removeElement($sonBatch)) {
            // set the owning side to null (unless already changed)
            if ($sonBatch->getFatherBatch() === $this) {
                $sonBatch->setFatherBatch(null);
            }
        }

        return $this;
    }
}
