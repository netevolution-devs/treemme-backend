<?php

namespace App\Entity;

use App\Repository\ColorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ColorRepository::class)]
class Color
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['color_list', 'color_detail', 'color_type_detail', 'product_list', 'product_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'colors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['color_list', 'color_detail'])]
    private ?ColorType $color_type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['color_list', 'color_detail', 'color_type_detail', 'product_list', 'product_detail'])]
    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['color_list', 'color_detail', 'product_list', 'product_detail'])]
    private ?string $shade = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['color_list', 'color_detail'])]
    private ?string $var_color = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['color_detail'])]
    private ?string $color_note = null;

    #[ORM\Column(length: 255)]
    #[Groups(['color_list', 'color_detail'])]
    private ?string $client_color = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'color', targetEntity: Product::class)]
    #[Groups(['color_detail'])]
    private Collection $products;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColorType(): ?ColorType
    {
        return $this->color_type;
    }

    public function setColorType(?ColorType $color_type): static
    {
        $this->color_type = $color_type;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getShade(): ?string
    {
        return $this->shade;
    }

    public function setShade(?string $shade): static
    {
        $this->shade = $shade;

        return $this;
    }

    public function getVarColor(): ?string
    {
        return $this->var_color;
    }

    public function setVarColor(?string $var_color): static
    {
        $this->var_color = $var_color;

        return $this;
    }

    public function getColorNote(): ?string
    {
        return $this->color_note;
    }

    public function setColorNote(?string $color_note): static
    {
        $this->color_note = $color_note;

        return $this;
    }

    public function getClientColor(): ?string
    {
        return $this->client_color;
    }

    public function setClientColor(string $client_color): static
    {
        $this->client_color = $client_color;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setColor($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getColor() === $this) {
                $product->setColor(null);
            }
        }

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
