<?php

namespace App\Entity;

use App\Repository\CurrencyChangeRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CurrencyChangeRepository::class)]
class CurrencyChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['currency_change_list', 'currency_change_detail', 'currency_list', 'currency_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'currencyChanges')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['currency_change_detail'])]
    private ?Currency $currency = null;

    #[ORM\Column]
    #[Groups(['currency_change_list', 'currency_change_detail', 'currency_list', 'currency_detail'])]
    private ?\DateTime $date = null;

    #[ORM\Column]
    #[Groups(['currency_change_list', 'currency_change_detail', 'currency_list', 'currency_detail'])]
    private ?float $change_value = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getChangeValue(): ?float
    {
        return $this->change_value;
    }

    public function setChangeValue(float $change_value): static
    {
        $this->change_value = $change_value;

        return $this;
    }
}
