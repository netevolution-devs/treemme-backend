<?php

namespace App\Entity;

use App\Repository\SelectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SelectionRepository::class)]
class Selection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['batch_selection_detail', 'batch_detail', 'selection_list', 'selection_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['batch_selection_detail', 'batch_detail', 'selection_list', 'selection_detail'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $weight = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'selections')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $selections;

    /**
     * @var Collection<int, BatchSelection>
     */
    #[ORM\OneToMany(mappedBy: 'selection', targetEntity: BatchSelection::class, orphanRemoval: true)]
    private Collection $batchSelections;

    public function __construct()
    {
        $this->selections = new ArrayCollection();
        $this->batchSelections = new ArrayCollection();
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

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSelections(): Collection
    {
        return $this->selections;
    }

    public function addSelection(self $selection): static
    {
        if (!$this->selections->contains($selection)) {
            $this->selections->add($selection);
            $selection->setParent($this);
        }

        return $this;
    }

    public function removeSelection(self $selection): static
    {
        if ($this->selections->removeElement($selection)) {
            // set the owning side to null (unless already changed)
            if ($selection->getParent() === $this) {
                $selection->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BatchSelection>
     */
    public function getBatchSelections(): Collection
    {
        return $this->batchSelections;
    }

    public function addBatchSelection(BatchSelection $batchSelection): static
    {
        if (!$this->batchSelections->contains($batchSelection)) {
            $this->batchSelections->add($batchSelection);
            $batchSelection->setSelection($this);
        }

        return $this;
    }

    public function removeBatchSelection(BatchSelection $batchSelection): static
    {
        if ($this->batchSelections->removeElement($batchSelection)) {
            // set the owning side to null (unless already changed)
            if ($batchSelection->getSelection() === $this) {
                $batchSelection->setSelection(null);
            }
        }

        return $this;
    }
}
