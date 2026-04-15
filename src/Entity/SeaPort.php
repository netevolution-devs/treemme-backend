<?php

namespace App\Entity;

use App\Repository\SeaPortRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SeaPortRepository::class)]
class SeaPort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sea_port_list', 'sea_port_detail', 'batch_data_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['sea_port_list', 'sea_port_detail', 'batch_data_detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['sea_port_detail'])]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sea_port_detail'])]
    private ?int $deductible_day = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sea_port_detail'])]
    private ?float $parking_day_cost = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sea_port_detail'])]
    private ?int $container_deductible_day = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sea_port_detail'])]
    private ?float $container_parking_day_cost = null;

    /**
     * @var Collection<int, BatchData>
     */
    #[ORM\OneToMany(mappedBy: 'sea_port', targetEntity: BatchData::class)]
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDeductibleDay(): ?int
    {
        return $this->deductible_day;
    }

    public function setDeductibleDay(?int $deductible_day): static
    {
        $this->deductible_day = $deductible_day;

        return $this;
    }

    public function getParkingDayCost(): ?float
    {
        return $this->parking_day_cost;
    }

    public function setParkingDayCost(?float $parking_day_cost): static
    {
        $this->parking_day_cost = $parking_day_cost;

        return $this;
    }

    public function getContainerDeductibleDay(): ?int
    {
        return $this->container_deductible_day;
    }

    public function setContainerDeductibleDay(?int $container_deductible_day): static
    {
        $this->container_deductible_day = $container_deductible_day;

        return $this;
    }

    public function getContainerParkingDayCost(): ?float
    {
        return $this->container_parking_day_cost;
    }

    public function setContainerParkingDayCost(?float $container_parking_day_cost): static
    {
        $this->container_parking_day_cost = $container_parking_day_cost;

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
            $batchData->setSeaPort($this);
        }

        return $this;
    }

    public function removeBatchData(BatchData $batchData): static
    {
        if ($this->batchData->removeElement($batchData)) {
            // set the owning side to null (unless already changed)
            if ($batchData->getSeaPort() === $this) {
                $batchData->setSeaPort(null);
            }
        }

        return $this;
    }
}
