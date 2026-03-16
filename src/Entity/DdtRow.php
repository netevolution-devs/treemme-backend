<?php

namespace App\Entity;

use App\Repository\DdtRowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DdtRowRepository::class)]
class DdtRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?string $order_note = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?Batch $batch = null;

    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?int $pieces = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $total_value = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?Currency $currency = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?Ddt $ddt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $currency_price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $currency_change = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $currency_total_value = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_row_detail'])]
    private ?float $KG_weight = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?string $row_note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?int $whole_piece = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?int $half_piece = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_row_detail'])]
    private ?Selection $selection = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNote(): ?string
    {
        return $this->order_note;
    }

    public function setOrderNote(?string $order_note): static
    {
        $this->order_note = $order_note;

        return $this;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getTotalValue(): ?float
    {
        return $this->total_value;
    }

    public function setTotalValue(?float $total_value): static
    {
        $this->total_value = $total_value;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getDdt(): ?Ddt
    {
        return $this->ddt;
    }

    public function setDdt(?Ddt $ddt): static
    {
        $this->ddt = $ddt;

        return $this;
    }

    public function getCurrencyPrice(): ?float
    {
        return $this->currency_price;
    }

    public function setCurrencyPrice(?float $currency_price): static
    {
        $this->currency_price = $currency_price;

        return $this;
    }

    public function getCurrencyChange(): ?float
    {
        return $this->currency_change;
    }

    public function setCurrencyChange(?float $currency_change): static
    {
        $this->currency_change = $currency_change;

        return $this;
    }

    public function getCurrencyTotalValue(): ?float
    {
        return $this->currency_total_value;
    }

    public function setCurrencyTotalValue(?float $currency_total_value): static
    {
        $this->currency_total_value = $currency_total_value;

        return $this;
    }

    public function getKGWeight(): ?float
    {
        return $this->KG_weight;
    }

    public function setKGWeight(?float $KG_weight): static
    {
        $this->KG_weight = $KG_weight;

        return $this;
    }

    public function getRowNote(): ?string
    {
        return $this->row_note;
    }

    public function setRowNote(?string $row_note): static
    {
        $this->row_note = $row_note;

        return $this;
    }

    public function getWholePiece(): ?int
    {
        return $this->whole_piece;
    }

    public function setWholePiece(?int $whole_piece): static
    {
        $this->whole_piece = $whole_piece;

        return $this;
    }

    public function getHalfPiece(): ?int
    {
        return $this->half_piece;
    }

    public function setHalfPiece(?int $half_piece): static
    {
        $this->half_piece = $half_piece;

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
}
