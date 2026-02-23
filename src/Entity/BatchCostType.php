<?php

namespace App\Entity;

use App\Repository\BatchCostTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BatchCostTypeRepository::class)]
class BatchCostType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_cost_type_list', 'batch_cost_type_detail', 'batch_cost_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['batch_cost_type_list', 'batch_cost_type_detail', 'batch_cost_detail'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['batch_cost_type_list', 'batch_cost_type_detail', 'batch_cost_detail'])]
    private ?int $weight = null;

    /**
     * @var Collection<int, BatchCost>
     */
    #[ORM\OneToMany(mappedBy: 'batch_cost_type', targetEntity: BatchCost::class)]
    #[Groups(['batch_cost_type_detail'])]
    private Collection $batchCosts;

    public function __construct()
    {
        $this->batchCosts = new ArrayCollection();
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

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

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
            $batchCost->setBatchCostType($this);
        }

        return $this;
    }

    public function removeBatchCost(BatchCost $batchCost): static
    {
        if ($this->batchCosts->removeElement($batchCost)) {
            // set the owning side to null (unless already changed)
            if ($batchCost->getBatchCostType() === $this) {
                $batchCost->setBatchCostType(null);
            }
        }

        return $this;
    }
}
