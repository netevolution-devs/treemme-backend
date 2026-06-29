<?php

namespace App\Entity;

use App\Repository\DdtRowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DdtRowRepository::class)]
class DdtRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_summary_print', 'client_order_row_list', 'external_processing_print'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list'])]
    private ?string $order_note = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_summary_print', 'external_processing_print'])]
    private ?Batch $batch = null;

    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print', 'external_processing_print'])]
    private ?float $pieces = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print'])]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\Column]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print'])]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print'])]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print'])]
    private ?float $total_value = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_order_row_list', 'client_summary_print'])]
    private ?Currency $currency = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_summary_print', 'client_order_row_list', 'external_processing_print'])]
    private ?Ddt $ddt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail'])]
    private ?float $currency_price = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail'])]
    private ?float $currency_exchange = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail'])]
    private ?float $currency_total_value = null;

    #[ORM\Column(nullable: true)]
    #[SerializedName('kg_weight')]
    #[Groups(['ddt_row_detail'])]
    private ?float $KG_weight = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail'])]
    private ?string $row_note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail'])]
    private ?int $whole_piece = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_list_sold', 'ddt_row_detail', 'client_summary_print'])]
    private ?int $half_piece = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[Groups(['ddt_row_detail'])]
    private ?Selection $selection = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $pieces_out = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?float $quantity_out = null;

    /**
     * @var Collection<int, DdtRowProcessing>
     */
    #[ORM\OneToMany(mappedBy: 'ddt_row', targetEntity: DdtRowProcessing::class, orphanRemoval: true)]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail', 'external_processing_print'])]
    private Collection $ddtRowProcessings;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subcontractor_ddt_number = null;

    public function __construct()
    {
        $this->ddtRowProcessings = new ArrayCollection();
    }

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

    public function getPieces(): ?float
    {
        return $this->pieces;
    }

    public function setPieces(float $pieces): static
    {
        $batch = $this->getBatch();
        if ($batch && $batch->getHalfPiecesCount() === null && (floor($pieces) != $pieces)) {
            $pieces = round($pieces);
        }
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
        $this->quantity = round($quantity, 3);

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

    public function getCurrencyExchange(): ?float
    {
        return $this->currency_exchange;
    }

    public function setCurrencyExchange(?float $currency_exchange): static
    {
        $this->currency_exchange = $currency_exchange;

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

    public function getPiecesOut(): ?float
    {
        return $this->pieces_out;
    }

    public function setPiecesOut(?float $pieces_out): static
    {
        if ($pieces_out !== null) {
            $batch = $this->getBatch();
            if ($batch && $batch->getHalfPiecesCount() === null && (floor($pieces_out) != $pieces_out)) {
                $pieces_out = round($pieces_out);
            }
        }
        $this->pieces_out = $pieces_out;

        return $this;
    }

    public function getQuantityOut(): ?float
    {
        return $this->quantity_out;
    }

    public function setQuantityOut(?float $quantity_out): static
    {
        $this->quantity_out = $quantity_out;

        return $this;
    }

    /**
     * @return Collection<int, DdtRowProcessing>
     */
    public function getDdtRowProcessings(): Collection
    {
        return $this->ddtRowProcessings;
    }

    public function addDdtRowProcessing(DdtRowProcessing $ddtRowProcessing): static
    {
        if (!$this->ddtRowProcessings->contains($ddtRowProcessing)) {
            $this->ddtRowProcessings->add($ddtRowProcessing);
            $ddtRowProcessing->setDdtRow($this);
        }

        return $this;
    }

    public function removeDdtRowProcessing(DdtRowProcessing $ddtRowProcessing): static
    {
        if ($this->ddtRowProcessings->removeElement($ddtRowProcessing)) {
            // set the owning side to null (unless already changed)
            if ($ddtRowProcessing->getDdtRow() === $this) {
                $ddtRowProcessing->setDdtRow(null);
            }
        }

        return $this;
    }

    public function getSubcontractorDdtNumber(): ?string
    {
        return $this->subcontractor_ddt_number;
    }

    public function setSubcontractorDdtNumber(?string $subcontractor_ddt_number): static
    {
        $this->subcontractor_ddt_number = $subcontractor_ddt_number;

        return $this;
    }
}
