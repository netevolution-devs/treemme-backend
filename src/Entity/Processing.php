<?php

namespace App\Entity;

use App\Repository\ProcessingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProcessingRepository::class)]
class Processing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['processing_list', 'processing_detail', 'material_bill_detail', 'recipe_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['processing_list', 'processing_detail', 'material_bill_detail', 'recipe_detail'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['processing_list', 'processing_detail'])]
    private ?bool $external = null;

    #[ORM\Column]
    #[Groups(['processing_list', 'processing_detail'])]
    private ?bool $color_recipe = null;

    #[ORM\Column]
    #[Groups(['processing_list', 'processing_detail'])]
    private ?bool $final_check = null;

    #[ORM\Column]
    #[Groups(['processing_list', 'processing_detail'])]
    private ?bool $processing_recipe = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\OneToMany(mappedBy: 'processing', targetEntity: Recipe::class)]
    private Collection $recipes;

    /**
     * @var Collection<int, MaterialBillStep>
     */
    #[ORM\OneToMany(mappedBy: 'processing', targetEntity: MaterialBillStep::class)]
    private Collection $materialBillSteps;

    public function __construct()
    {
        $this->recipes = new ArrayCollection();
        $this->materialBillSteps = new ArrayCollection();
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

    public function isExternal(): ?bool
    {
        return $this->external;
    }

    public function setExternal(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    public function isColorRecipe(): ?bool
    {
        return $this->color_recipe;
    }

    public function setColorRecipe(bool $color_recipe): static
    {
        $this->color_recipe = $color_recipe;

        return $this;
    }

    public function isFinalCheck(): ?bool
    {
        return $this->final_check;
    }

    public function setFinalCheck(bool $final_check): static
    {
        $this->final_check = $final_check;

        return $this;
    }

    public function isProcessingRecipe(): ?bool
    {
        return $this->processing_recipe;
    }

    public function setProcessingRecipe(bool $processing_recipe): static
    {
        $this->processing_recipe = $processing_recipe;

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): static
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
            $recipe->setProcessing($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): static
    {
        if ($this->recipes->removeElement($recipe)) {
            // set the owning side to null (unless already changed)
            if ($recipe->getProcessing() === $this) {
                $recipe->setProcessing(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaterialBillStep>
     */
    public function getMaterialBillSteps(): Collection
    {
        return $this->materialBillSteps;
    }

    public function addMaterialBillStep(MaterialBillStep $materialBillStep): static
    {
        if (!$this->materialBillSteps->contains($materialBillStep)) {
            $this->materialBillSteps->add($materialBillStep);
            $materialBillStep->setProcessing($this);
        }

        return $this;
    }

    public function removeMaterialBillStep(MaterialBillStep $materialBillStep): static
    {
        if ($this->materialBillSteps->removeElement($materialBillStep)) {
            // set the owning side to null (unless already changed)
            if ($materialBillStep->getProcessing() === $this) {
                $materialBillStep->setProcessing(null);
            }
        }

        return $this;
    }
}
