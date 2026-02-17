<?php

namespace App\Entity;

use App\Repository\MaterialBillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MaterialBillRepository::class)]
class MaterialBill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['material_bill_list', 'material_bill_detail'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['material_bill_list', 'material_bill_detail'])]
    private ?int $review = null;

    #[ORM\ManyToOne(inversedBy: 'materialBills')]
    #[Groups(['material_bill_list', 'material_bill_detail'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['material_bill_detail'])]
    private ?string $bill_note = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

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
}
