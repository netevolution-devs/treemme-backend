<?php

namespace App\Entity;

use App\Repository\ColorTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ColorTypeRepository::class)]
class ColorType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Color>
     */
    #[ORM\OneToMany(mappedBy: 'color_type', targetEntity: Color::class)]
    private Collection $colors;

    public function __construct()
    {
        $this->colors = new ArrayCollection();
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
     * @return Collection<int, Color>
     */
    public function getColors(): Collection
    {
        return $this->colors;
    }

    public function addColor(Color $color): static
    {
        if (!$this->colors->contains($color)) {
            $this->colors->add($color);
            $color->setColorType($this);
        }

        return $this;
    }

    public function removeColor(Color $color): static
    {
        if ($this->colors->removeElement($color)) {
            // set the owning side to null (unless already changed)
            if ($color->getColorType() === $this) {
                $color->setColorType(null);
            }
        }

        return $this;
    }
}
