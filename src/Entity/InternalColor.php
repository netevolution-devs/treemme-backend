<?php

namespace App\Entity;

use App\Repository\InternalColorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: InternalColorRepository::class)]
class InternalColor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['internal_color_list', 'internal_color_detail', 'color_list', 'color_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['internal_color_list', 'internal_color_detail', 'color_list', 'color_detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Color>
     */
    #[ORM\OneToMany(mappedBy: 'internal_color', targetEntity: Color::class)]
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
            $color->setInternalColor($this);
        }

        return $this;
    }

    public function removeColor(Color $color): static
    {
        if ($this->colors->removeElement($color)) {
            // set the owning side to null (unless already changed)
            if ($color->getInternalColor() === $this) {
                $color->setInternalColor(null);
            }
        }

        return $this;
    }
}
