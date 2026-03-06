<?php

namespace App\Entity;

use App\Repository\RecipeTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RecipeTypeRepository::class)]
class RecipeType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recipe_type_list', 'recipe_type_detail', 'recipe_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recipe_type_list', 'recipe_type_detail', 'recipe_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\OneToMany(mappedBy: 'recipe_type', targetEntity: Recipe::class)]
    private Collection $recipes;

    public function __construct()
    {
        $this->recipes = new ArrayCollection();
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
            $recipe->setRecipeType($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): static
    {
        if ($this->recipes->removeElement($recipe)) {
            // set the owning side to null (unless already changed)
            if ($recipe->getRecipeType() === $this) {
                $recipe->setRecipeType(null);
            }
        }

        return $this;
    }
}
