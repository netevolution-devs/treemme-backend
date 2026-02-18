<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail'])]
    private ?string $abbreviation = null;

    #[ORM\Column(length: 255)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 1)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail'])]
    private ?string $sign = null;

    /**
     * @var Collection<int, BatchCost>
     */
    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: BatchCost::class)]
    #[Groups(['currency_detail'])]
    private Collection $batchCosts;

    public function __construct()
    {
        $this->batchCosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

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

    public function getSign(): ?string
    {
        return $this->sign;
    }

    public function setSign(string $sign): static
    {
        $this->sign = $sign;

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
            $batchCost->setCurrency($this);
        }

        return $this;
    }

    public function removeBatchCost(BatchCost $batchCost): static
    {
        if ($this->batchCosts->removeElement($batchCost)) {
            // set the owning side to null (unless already changed)
            if ($batchCost->getCurrency() === $this) {
                $batchCost->setCurrency(null);
            }
        }

        return $this;
    }
}
