<?php

namespace App\Entity;

use App\Repository\BatchTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchTypeRepository::class)]
class BatchType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $prefix = null;

    #[ORM\Column]
    private ?bool $sale_process = null;

    /**
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(mappedBy: 'batch_type', targetEntity: Batch::class)]
    private Collection $batches;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
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

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function isSaleProcess(): ?bool
    {
        return $this->sale_process;
    }

    public function setSaleProcess(bool $sale_process): static
    {
        $this->sale_process = $sale_process;

        return $this;
    }

    /**
     * @return Collection<int, Batch>
     */
    public function getBatches(): Collection
    {
        return $this->batches;
    }

    public function addBatch(Batch $batch): static
    {
        if (!$this->batches->contains($batch)) {
            $this->batches->add($batch);
            $batch->setBatchType($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getBatchType() === $this) {
                $batch->setBatchType(null);
            }
        }

        return $this;
    }
}
