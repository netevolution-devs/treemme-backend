<?php

namespace App\Entity;

use App\Repository\MeasurementUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MeasurementUnitRepository::class)]
class MeasurementUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['measurement_unit_list', 'measurement_unit_detail', 'batch_list', 'batch_detail', 'product_list', 'product_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['measurement_unit_list', 'measurement_unit_detail', 'batch_list', 'batch_detail', 'product_list', 'product_detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['measurement_unit_list', 'measurement_unit_detail', 'batch_list', 'batch_detail', 'product_list', 'product_detail'])]
    private ?string $prefix = null;

    /**
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(mappedBy: 'measurement_unit', targetEntity: Batch::class)]
    #[Groups(['measurement_unit_detail'])]
    private Collection $batches;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'measurement_unit', targetEntity: Product::class)]
    #[Groups(['measurement_unit_detail'])]
    private Collection $products;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, ClientOrderRow>
     */
    #[ORM\OneToMany(mappedBy: 'measurement_unit', targetEntity: ClientOrderRow::class)]
    private Collection $clientOrderRows;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->clientOrderRows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return Collection<int, Batch>
     */
    public function getBatches(): Collection
    {
        return $this->batches;
    }

    public function addBatch(Batch $batch): static
    {
        if (!$this->batches->contains($batch)) {
            $this->batches->add($batch);
            $batch->setMeasurementUnit($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getMeasurementUnit() === $this) {
                $batch->setMeasurementUnit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setMeasurementUnit($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getMeasurementUnit() === $this) {
                $product->setMeasurementUnit(null);
            }
        }

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
     * @return Collection<int, ClientOrderRow>
     */
    public function getClientOrderRows(): Collection
    {
        return $this->clientOrderRows;
    }

    public function addClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if (!$this->clientOrderRows->contains($clientOrderRow)) {
            $this->clientOrderRows->add($clientOrderRow);
            $clientOrderRow->setMeasurementUnit($this);
        }

        return $this;
    }

    public function removeClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if ($this->clientOrderRows->removeElement($clientOrderRow)) {
            // set the owning side to null (unless already changed)
            if ($clientOrderRow->getMeasurementUnit() === $this) {
                $clientOrderRow->setMeasurementUnit(null);
            }
        }

        return $this;
    }
}
