<?php

namespace App\Entity;

use App\Repository\MaterialBillStepRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MaterialBillStepRepository::class)]
class MaterialBillStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['material_bill_step_list', 'material_bill_step_detail', 'material_bill_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'materialBillSteps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['material_bill_step_list', 'material_bill_step_detail', 'material_bill_detail'])]
    private ?MaterialBill $material_bill = null;

    #[ORM\ManyToOne(inversedBy: 'materialBillSteps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['material_bill_step_list', 'material_bill_step_detail', 'material_bill_detail'])]
    private ?Processing $processing = null;

    #[ORM\ManyToOne(inversedBy: 'materialBillSteps')]
    #[Groups(['material_bill_step_list', 'material_bill_step_detail', 'material_bill_detail'])]
    private ?Recipe $recipe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaterialBill(): ?MaterialBill
    {
        return $this->material_bill;
    }

    public function setMaterialBill(?MaterialBill $material_bill): static
    {
        $this->material_bill = $material_bill;

        return $this;
    }

    public function getProcessing(): ?Processing
    {
        return $this->processing;
    }

    public function setProcessing(?Processing $processing): static
    {
        $this->processing = $processing;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }
}
