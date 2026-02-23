<?php

namespace App\Entity;

use App\Repository\LeatherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeatherRepository::class)]
class Leather
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['leather_list', 'leather_detail', 'batch_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['leather_list', 'leather_detail', 'batch_detail'])]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Groups(['leather_list', 'leather_detail', 'batch_detail'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $sqft_leather_min = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $sqft_leather_max = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $sqft_leather_media = null;

    #[ORM\Column]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $sqft_leather_expected = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $kg_leather_min = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $kg_leather_max = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $kg_leather_media = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $kg_leather_expected = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?int $container_piece = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?bool $statistic_update = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?float $crust_revenue_expected = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherWeight $weight = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherSpecies $species = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherThickness $thickness = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherFlay $flay = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherProvenance $provenance = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherType $type = null;

    #[ORM\ManyToOne(inversedBy: 'leather')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['leather_list', 'leather_detail'])]
    private ?LeatherStatus $status = null;

    /**
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(mappedBy: 'leather', targetEntity: Batch::class, orphanRemoval: true)]
    private Collection $batches;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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

    public function getSqftLeatherMin(): ?float
    {
        return $this->sqft_leather_min;
    }

    public function setSqftLeatherMin(?float $sqft_leather_min): static
    {
        $this->sqft_leather_min = $sqft_leather_min;

        return $this;
    }

    public function getSqftLeatherMax(): ?float
    {
        return $this->sqft_leather_max;
    }

    public function setSqftLeatherMax(?float $sqft_leather_max): static
    {
        $this->sqft_leather_max = $sqft_leather_max;

        return $this;
    }

    public function getSqftLeatherMedia(): ?float
    {
        return $this->sqft_leather_media;
    }

    public function setSqftLeatherMedia(?float $sqft_leather_media): static
    {
        $this->sqft_leather_media = $sqft_leather_media;

        return $this;
    }

    public function getSqftLeatherExpected(): ?float
    {
        return $this->sqft_leather_expected;
    }

    public function setSqftLeatherExpected(float $sqft_leather_expected): static
    {
        $this->sqft_leather_expected = $sqft_leather_expected;

        return $this;
    }

    public function getKgLeatherMin(): ?float
    {
        return $this->kg_leather_min;
    }

    public function setKgLeatherMin(?float $kg_leather_min): static
    {
        $this->kg_leather_min = $kg_leather_min;

        return $this;
    }

    public function getKgLeatherMax(): ?float
    {
        return $this->kg_leather_max;
    }

    public function setKgLeatherMax(?float $kg_leather_max): static
    {
        $this->kg_leather_max = $kg_leather_max;

        return $this;
    }

    public function getKgLeatherMedia(): ?float
    {
        return $this->kg_leather_media;
    }

    public function setKgLeatherMedia(?float $kg_leather_media): static
    {
        $this->kg_leather_media = $kg_leather_media;

        return $this;
    }

    public function getKgLeatherExpected(): ?float
    {
        return $this->kg_leather_expected;
    }

    public function setKgLeatherExpected(?float $kg_leather_expected): static
    {
        $this->kg_leather_expected = $kg_leather_expected;

        return $this;
    }

    public function getContainerPiece(): ?int
    {
        return $this->container_piece;
    }

    public function setContainerPiece(?int $container_piece): static
    {
        $this->container_piece = $container_piece;

        return $this;
    }

    public function isStatisticUpdate(): ?bool
    {
        return $this->statistic_update;
    }

    public function setStatisticUpdate(?bool $statistic_update): static
    {
        $this->statistic_update = $statistic_update;

        return $this;
    }

    public function getCrustRevenueExpected(): ?float
    {
        return $this->crust_revenue_expected;
    }

    public function setCrustRevenueExpected(?float $crust_revenue_expected): static
    {
        $this->crust_revenue_expected = $crust_revenue_expected;

        return $this;
    }

    public function getWeight(): ?LeatherWeight
    {
        return $this->weight;
    }

    public function setWeight(?LeatherWeight $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getSpecies(): ?LeatherSpecies
    {
        return $this->species;
    }

    public function setSpecies(?LeatherSpecies $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getThickness(): ?LeatherThickness
    {
        return $this->thickness;
    }

    public function setThickness(?LeatherThickness $thickness): static
    {
        $this->thickness = $thickness;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getFlay(): ?LeatherFlay
    {
        return $this->flay;
    }

    public function setFlay(?LeatherFlay $flay): static
    {
        $this->flay = $flay;

        return $this;
    }

    public function getProvenance(): ?LeatherProvenance
    {
        return $this->provenance;
    }

    public function setProvenance(?LeatherProvenance $provenance): static
    {
        $this->provenance = $provenance;

        return $this;
    }

    public function getType(): ?LeatherType
    {
        return $this->type;
    }

    public function setType(?LeatherType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?LeatherStatus
    {
        return $this->status;
    }

    public function setStatus(?LeatherStatus $status): static
    {
        $this->status = $status;

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
            $batch->setLeather($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getLeather() === $this) {
                $batch->setLeather(null);
            }
        }

        return $this;
    }
}
