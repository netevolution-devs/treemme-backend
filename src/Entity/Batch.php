<?php

namespace App\Entity;

use App\Repository\BatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $completed = null;

    #[ORM\Column]
    private ?bool $checked = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    private ?BatchType $batch_type = null;

    #[ORM\Column(length: 50)]
    private ?string $batch_code = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $batch_date = null;

    #[ORM\Column]
    private ?int $pieces = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column]
    private ?float $quantity = null;

    #[ORM\Column]
    private ?float $stock_items = null;

    #[ORM\Column]
    private ?float $storage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $selection_note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $batch_note = null;

    #[ORM\Column]
    private ?bool $sampling = null;

    #[ORM\Column]
    private ?bool $split_selected = null;

    #[ORM\Column]
    private ?float $sq_ft_avarage_expected = null;

    #[ORM\Column]
    private ?float $sq_ft_avarage_found = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $check_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $check_note = null;

    #[ORM\ManyToOne(inversedBy: 'batches')]
    private ?User $check_user = null;


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
}
