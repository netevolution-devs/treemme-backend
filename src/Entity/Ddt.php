<?php

namespace App\Entity;

use App\Repository\DdtRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DdtRepository::class)]
class Ddt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ddt_number = null;

    #[ORM\Column]
    private ?\DateTime $ddt_date = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $ddt_start_date = null;

    #[ORM\ManyToOne(inversedBy: 'ddts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $subcontractor = null;

    #[ORM\ManyToOne(inversedBy: 'ddts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DdtPurpose $ddt_purpose = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDdtNumber(): ?string
    {
        return $this->ddt_number;
    }

    public function setDdtNumber(string $ddt_number): static
    {
        $this->ddt_number = $ddt_number;

        return $this;
    }

    public function getDdtDate(): ?\DateTime
    {
        return $this->ddt_date;
    }

    public function setDdtDate(\DateTime $ddt_date): static
    {
        $this->ddt_date = $ddt_date;

        return $this;
    }

    public function getDdtStartDate(): ?\DateTime
    {
        return $this->ddt_start_date;
    }

    public function setDdtStartDate(?\DateTime $ddt_start_date): static
    {
        $this->ddt_start_date = $ddt_start_date;

        return $this;
    }

    public function getSubcontractor(): ?Contact
    {
        return $this->subcontractor;
    }

    public function setSubcontractor(?Contact $subcontractor): static
    {
        $this->subcontractor = $subcontractor;

        return $this;
    }

    public function getDdtPurpose(): ?DdtPurpose
    {
        return $this->ddt_purpose;
    }

    public function setDdtPurpose(?DdtPurpose $ddt_purpose): static
    {
        $this->ddt_purpose = $ddt_purpose;

        return $this;
    }
}
