<?php

namespace App\Entity;

use App\Repository\LeatherRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeatherRepository::class)]
class Leather
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $sqft_leather_min = null;

    #[ORM\Column(nullable: true)]
    private ?float $sqft_leather_max = null;

    #[ORM\Column(nullable: true)]
    private ?float $sqft_leather_media = null;

    #[ORM\Column]
    private ?float $sqft_leather_expected = null;

    #[ORM\Column(nullable: true)]
    private ?float $kg_leather_min = null;

    #[ORM\Column(nullable: true)]
    private ?float $kg_leather_max = null;

    #[ORM\Column(nullable: true)]
    private ?float $kg_leather_media = null;

    #[ORM\Column(nullable: true)]
    private ?float $kg_leather_expected = null;

    #[ORM\Column(nullable: true)]
    private ?int $container_piece = null;

    #[ORM\Column(nullable: true)]
    private ?bool $statistic_update = null;

    #[ORM\Column(nullable: true)]
    private ?float $crust_revenue_expected = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    private ?LeatherWeight $weight = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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

    public function getSqftLeatherMin(): ?float
    {
        return $this->sqft_leather_min;
    }

    public function setSqftLeatherMin(?float $sqft_leather_min): static
    {
        $this->sqft_leather_min = $sqft_leather_min;

        return $this;
    }

    public function getSqftLeatherMax(): ?float
    {
        return $this->sqft_leather_max;
    }

    public function setSqftLeatherMax(?float $sqft_leather_max): static
    {
        $this->sqft_leather_max = $sqft_leather_max;

        return $this;
    }

    public function getSqftLeatherMedia(): ?float
    {
        return $this->sqft_leather_media;
    }

    public function setSqftLeatherMedia(?float $sqft_leather_media): static
    {
        $this->sqft_leather_media = $sqft_leather_media;

        return $this;
    }

    public function getSqftLeatherExpected(): ?float
    {
        return $this->sqft_leather_expected;
    }

    public function setSqftLeatherExpected(float $sqft_leather_expected): static
    {
        $this->sqft_leather_expected = $sqft_leather_expected;

        return $this;
    }

    public function getKgLeatherMin(): ?float
    {
        return $this->kg_leather_min;
    }

    public function setKgLeatherMin(?float $kg_leather_min): static
    {
        $this->kg_leather_min = $kg_leather_min;

        return $this;
    }

    public function getKgLeatherMax(): ?float
    {
        return $this->kg_leather_max;
    }

    public function setKgLeatherMax(?float $kg_leather_max): static
    {
        $this->kg_leather_max = $kg_leather_max;

        return $this;
    }

    public function getKgLeatherMedia(): ?float
    {
        return $this->kg_leather_media;
    }

    public function setKgLeatherMedia(?float $kg_leather_media): static
    {
        $this->kg_leather_media = $kg_leather_media;

        return $this;
    }

    public function getKgLeatherExpected(): ?float
    {
        return $this->kg_leather_expected;
    }

    public function setKgLeatherExpected(?float $kg_leather_expected): static
    {
        $this->kg_leather_expected = $kg_leather_expected;

        return $this;
    }

    public function getContainerPiece(): ?int
    {
        return $this->container_piece;
    }

    public function setContainerPiece(?int $container_piece): static
    {
        $this->container_piece = $container_piece;

        return $this;
    }

    public function isStatisticUpdate(): ?bool
    {
        return $this->statistic_update;
    }

    public function setStatisticUpdate(?bool $statistic_update): static
    {
        $this->statistic_update = $statistic_update;

        return $this;
    }

    public function getCrustRevenueExpected(): ?float
    {
        return $this->crust_revenue_expected;
    }

    public function setCrustRevenueExpected(?float $crust_revenue_expected): static
    {
        $this->crust_revenue_expected = $crust_revenue_expected;

        return $this;
    }

    public function getWeight(): ?LeatherWeight
    {
        return $this->weight;
    }

    public function setWeight(?LeatherWeight $weight): static
    {
        $this->weight = $weight;

        return $this;
    }
}
