<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recipe_list', 'recipe_detail', 'product_detail', 'material_bill_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[Groups(['recipe_list', 'recipe_detail'])]
    private ?RecipeType $recipe_type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recipe_list', 'recipe_detail', 'product_detail', 'material_bill_detail'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['recipe_list', 'recipe_detail', 'product_detail', 'material_bill_detail'])]
    private ?int $review = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[Groups(['recipe_list', 'recipe_detail'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[Groups(['recipe_list', 'recipe_detail'])]
    private ?Processing $processing = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['recipe_detail'])]
    private ?string $recipe_note = null;

    /**
     * @var Collection<int, MaterialBillStep>
     */
    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: MaterialBillStep::class)]
    private Collection $materialBillSteps;

    public function __construct()
    {
        $this->materialBillSteps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipeType(): ?RecipeType
    {
        return $this->recipe_type;
    }

    public function setRecipeType(?RecipeType $recipe_type): static
    {
        $this->recipe_type = $recipe_type;

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

    public function getProcessing(): ?Processing
    {
        return $this->processing;
    }

    public function setProcessing(?Processing $processing): static
    {
        $this->processing = $processing;

        return $this;
    }

    public function getRecipeNote(): ?string
    {
        return $this->recipe_note;
    }

    public function setRecipeNote(?string $recipe_note): static
    {
        $this->recipe_note = $recipe_note;

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
            $materialBillStep->setRecipe($this);
        }

        return $this;
    }

    public function removeMaterialBillStep(MaterialBillStep $materialBillStep): static
    {
        if ($this->materialBillSteps->removeElement($materialBillStep)) {
            // set the owning side to null (unless already changed)
            if ($materialBillStep->getRecipe() === $this) {
                $materialBillStep->setRecipe(null);
            }
        }

        return $this;
    }
}
