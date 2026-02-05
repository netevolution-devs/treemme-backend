<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $product_code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $internal_name = null;

    #[ORM\Column(length: 255)]
    private ?string $external_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vendor_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $product_note = null;

    #[ORM\Column]
    private ?bool $exclude_mrp = null;

    #[ORM\Column(nullable: true)]
    private ?int $alarm = null;

    #[ORM\Column(nullable: true)]
    private ?float $stock = null;

    #[ORM\Column(nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(nullable: true)]
    private ?float $thickness = null;

    #[ORM\Column(nullable: true)]
    private ?float $use_coefficient = null;

    #[ORM\Column(nullable: true)]
    private ?float $bill_of_material_quantity = null;

    #[ORM\Column(nullable: true)]
    private ?float $last_cost = null;

    #[ORM\Column(nullable: true)]
    private ?float $last_price = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?ProductType $product_type = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MeasurementUnit $measurement_unit = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Color $color = null;

    /**
     * @var Collection<int, MaterialBill>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: MaterialBill::class)]
    private Collection $materialBills;

    public function __construct()
    {
        $this->materialBills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductCode(): ?string
    {
        return $this->product_code;
    }

    public function setProductCode(?string $product_code): static
    {
        $this->product_code = $product_code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getInternalName(): ?string
    {
        return $this->internal_name;
    }

    public function setInternalName(string $internal_name): static
    {
        $this->internal_name = $internal_name;

        return $this;
    }

    public function getExternalName(): ?string
    {
        return $this->external_name;
    }

    public function setExternalName(string $external_name): static
    {
        $this->external_name = $external_name;

        return $this;
    }

    public function getVendorCode(): ?string
    {
        return $this->vendor_code;
    }

    public function setVendorCode(?string $vendor_code): static
    {
        $this->vendor_code = $vendor_code;

        return $this;
    }

    public function getProductNote(): ?string
    {
        return $this->product_note;
    }

    public function setProductNote(?string $product_note): static
    {
        $this->product_note = $product_note;

        return $this;
    }

    public function isExcludeMrp(): ?bool
    {
        return $this->exclude_mrp;
    }

    public function setExcludeMrp(bool $exclude_mrp): static
    {
        $this->exclude_mrp = $exclude_mrp;

        return $this;
    }

    public function getAlarm(): ?int
    {
        return $this->alarm;
    }

    public function setAlarm(?int $alarm): static
    {
        $this->alarm = $alarm;

        return $this;
    }

    public function getStock(): ?float
    {
        return $this->stock;
    }

    public function setStock(?float $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getThickness(): ?float
    {
        return $this->thickness;
    }

    public function setThickness(?float $thickness): static
    {
        $this->thickness = $thickness;

        return $this;
    }

    public function getUseCoefficient(): ?float
    {
        return $this->use_coefficient;
    }

    public function setUseCoefficient(?float $use_coefficient): static
    {
        $this->use_coefficient = $use_coefficient;

        return $this;
    }

    public function getBillOfMaterialQuantity(): ?float
    {
        return $this->bill_of_material_quantity;
    }

    public function setBillOfMaterialQuantity(?float $bill_of_material_quantity): static
    {
        $this->bill_of_material_quantity = $bill_of_material_quantity;

        return $this;
    }

    public function getLastCost(): ?float
    {
        return $this->last_cost;
    }

    public function setLastCost(?float $last_cost): static
    {
        $this->last_cost = $last_cost;

        return $this;
    }

    public function getLastPrice(): ?float
    {
        return $this->last_price;
    }

    public function setLastPrice(?float $last_price): static
    {
        $this->last_price = $last_price;

        return $this;
    }

    public function getProductType(): ?ProductType
    {
        return $this->product_type;
    }

    public function setProductType(?ProductType $product_type): static
    {
        $this->product_type = $product_type;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

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

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, MaterialBill>
     */
    public function getMaterialBills(): Collection
    {
        return $this->materialBills;
    }

    public function addMaterialBill(MaterialBill $materialBill): static
    {
        if (!$this->materialBills->contains($materialBill)) {
            $this->materialBills->add($materialBill);
            $materialBill->setProduct($this);
        }

        return $this;
    }

    public function removeMaterialBill(MaterialBill $materialBill): static
    {
        if ($this->materialBills->removeElement($materialBill)) {
            // set the owning side to null (unless already changed)
            if ($materialBill->getProduct() === $this) {
                $materialBill->setProduct(null);
            }
        }

        return $this;
    }
}
