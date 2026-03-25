<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail', 'client_order_row_detail', 'client_order_detail', 'ddt_row_detail',
        'currency_change_list', 'currency_change_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail', 'client_order_row_detail', 'client_order_detail', 'ddt_row_detail',
        'currency_change_list', 'currency_change_detail'])]
    private ?string $abbreviation = null;

    #[ORM\Column(length: 255)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail', 'client_order_row_detail', 'client_order_detail', 'ddt_row_detail',
        'currency_change_list', 'currency_change_detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 1)]
    #[Groups(['currency_list', 'currency_detail', 'batch_cost_detail', 'client_order_row_detail', 'client_order_detail', 'ddt_row_detail',
        'currency_change_list', 'currency_change_detail'])]
    private ?string $sign = null;

    /**
     * @var Collection<int, BatchCost>
     */
    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: BatchCost::class)]
    #[Groups(['currency_detail'])]
    private Collection $batchCosts;

    /**
     * @var Collection<int, ClientOrderRow>
     */
    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: ClientOrderRow::class)]
    private Collection $clientOrderRows;

    /**
     * @var Collection<int, DdtRow>
     */
    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: DdtRow::class)]
    private Collection $ddtRows;

    /**
     * @var Collection<int, CurrencyChange>
     */
    #[ORM\OneToMany(mappedBy: 'currency', targetEntity: CurrencyChange::class, orphanRemoval: true)]
    private Collection $currencyChanges;

    public function __construct()
    {
        $this->batchCosts = new ArrayCollection();
        $this->clientOrderRows = new ArrayCollection();
        $this->ddtRows = new ArrayCollection();
        $this->currencyChanges = new ArrayCollection();
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
            $clientOrderRow->setCurrency($this);
        }

        return $this;
    }

    public function removeClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if ($this->clientOrderRows->removeElement($clientOrderRow)) {
            // set the owning side to null (unless already changed)
            if ($clientOrderRow->getCurrency() === $this) {
                $clientOrderRow->setCurrency(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DdtRow>
     */
    public function getDdtRows(): Collection
    {
        return $this->ddtRows;
    }

    public function addDdtRow(DdtRow $ddtRow): static
    {
        if (!$this->ddtRows->contains($ddtRow)) {
            $this->ddtRows->add($ddtRow);
            $ddtRow->setCurrency($this);
        }

        return $this;
    }

    public function removeDdtRow(DdtRow $ddtRow): static
    {
        if ($this->ddtRows->removeElement($ddtRow)) {
            // set the owning side to null (unless already changed)
            if ($ddtRow->getCurrency() === $this) {
                $ddtRow->setCurrency(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CurrencyChange>
     */
    public function getCurrencyChanges(): Collection
    {
        return $this->currencyChanges;
    }

    public function addCurrencyChange(CurrencyChange $currencyChange): static
    {
        if (!$this->currencyChanges->contains($currencyChange)) {
            $this->currencyChanges->add($currencyChange);
            $currencyChange->setCurrency($this);
        }

        return $this;
    }

    public function removeCurrencyChange(CurrencyChange $currencyChange): static
    {
        if ($this->currencyChanges->removeElement($currencyChange)) {
            // set the owning side to null (unless already changed)
            if ($currencyChange->getCurrency() === $this) {
                $currencyChange->setCurrency(null);
            }
        }

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName("last_change")]
    #[Groups(['currency_list', 'currency_detail'])]
    public function getLastChange(): ?CurrencyChange
    {
        if ($this->currencyChanges->isEmpty()) {
            return null;
        }

        $now = new \DateTimeImmutable('now');
        $from = $now->setTime(0, 0)->modify('-1 day');
        $to = $now->modify('+1 day')->setTime(0, 0);

        $changes = array_values(array_filter(
            $this->currencyChanges->toArray(),
            static function (CurrencyChange $change) use ($from, $to): bool {
                $date = $change->getDate();

                return $date >= $from && $date < $to;
            }
        ));

        if ($changes === []) {
            return null;
        }

        usort($changes, function ($a, $b) {
            return $b->getDate() <=> $a->getDate() ?: $b->getId() <=> $a->getId();
        });

        return $changes[0];
    }
}
