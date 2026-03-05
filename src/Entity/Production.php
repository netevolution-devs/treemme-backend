<?php

namespace App\Entity;

use App\Repository\ProductionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionRepository::class)]
class Production
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'productions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Machine $machine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $production_note = null;

    #[ORM\Column]
    private ?\DateTime $scheduled_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getMachine(): ?Machine
    {
        return $this->machine;
    }

    public function setMachine(?Machine $machine): static
    {
        $this->machine = $machine;

        return $this;
    }

    public function getProductionNote(): ?string
    {
        return $this->production_note;
    }

    public function setProductionNote(?string $production_note): static
    {
        $this->production_note = $production_note;

        return $this;
    }

    public function getScheduledDate(): ?\DateTime
    {
        return $this->scheduled_date;
    }

    public function setScheduledDate(\DateTime $scheduled_date): static
    {
        $this->scheduled_date = $scheduled_date;

        return $this;
    }
}
