<?php

namespace App\Entity;

use App\Repository\MaterialBillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaterialBillRepository::class)]
class MaterialBill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $review = null;

    #[ORM\ManyToOne(inversedBy: 'materialBills')]
    private ?Product $product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bill_note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?int
    {
        return $this->review;
    }

    public function setReview(int $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getBillNote(): ?string
    {
        return $this->bill_note;
    }

    public function setBillNote(?string $bill_note): static
    {
        $this->bill_note = $bill_note;

        return $this;
    }
}
