<?php

namespace App\Entity;

use App\Repository\PalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PalletRepository::class)]
class Pallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pallet_list', 'pallet_detail', 'batch_data_list', 'batch_data_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pallet_list', 'pallet_detail', 'batch_data_list', 'batch_data_detail'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['pallet_list', 'pallet_detail', 'batch_data_list', 'batch_data_detail'])]
    private ?float $weight = null;

    #[ORM\ManyToOne(inversedBy: 'pallets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['pallet_list', 'pallet_detail'])]
    private ?MeasurementUnit $measurement_unit = null;

    /**
     * @var Collection<int, BatchData>
     */
    #[ORM\OneToMany(mappedBy: 'pallet', targetEntity: BatchData::class)]
    private Collection $batchData;

    public function __construct()
    {
        $this->batchData = new ArrayCollection();
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

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = round($weight, 2);

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

    /**
     * @return Collection<int, BatchData>
     */
    public function getBatchData(): Collection
    {
        return $this->batchData;
    }

    public function addBatchData(BatchData $batchData): static
    {
        if (!$this->batchData->contains($batchData)) {
            $this->batchData->add($batchData);
            $batchData->setPallet($this);
        }

        return $this;
    }

    public function removeBatchData(BatchData $batchData): static
    {
        if ($this->batchData->removeElement($batchData)) {
            // set the owning side to null (unless already changed)
            if ($batchData->getPallet() === $this) {
                $batchData->setPallet(null);
            }
        }

        return $this;
    }
}
