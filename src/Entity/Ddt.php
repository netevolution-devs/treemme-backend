<?php

namespace App\Entity;

use App\Repository\DdtRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DdtRepository::class)]
class Ddt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ddt_list', 'ddt_detail', 'ddt_row_list', 'ddt_row_detail', 'client_summary_print', 'client_order_row_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ddt_list', 'ddt_detail', 'client_summary_print', 'client_order_row_list'])]
    private ?string $ddt_number = null;

    #[ORM\Column]
    #[Groups(['ddt_list', 'ddt_detail', 'client_summary_print', 'client_order_row_list'])]
    private ?\DateTime $ddt_date = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ddt_list', 'ddt_detail'])]
    private ?\DateTime $ddt_start_date = null;

    #[ORM\ManyToOne(inversedBy: 'ddts')]
    #[Groups(['ddt_list', 'ddt_detail'])]
    private ?Contact $subcontractor = null;

    /**
     * @var Collection<int, DdtRow>
     */
    #[ORM\OneToMany(mappedBy: 'ddt', targetEntity: DdtRow::class, orphanRemoval: true)]
    #[Groups(['ddt_detail'])]
    private Collection $ddtRows;

    #[ORM\ManyToOne(inversedBy: 'ddts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_list', 'ddt_detail', 'client_summary_print'])]
    private ?DdtReason $reason = null;

    #[ORM\ManyToOne(inversedBy: 'ddtsFromClient')]
    #[Groups(['ddt_list', 'ddt_detail'])]
    private ?Contact $client = null;

    public function __construct()
    {
        $this->ddtRows = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, DdtRow>
     */
    public function getDdtRows(): Collection
    {
        return $this->ddtRows;
    }

    public function addDdtRow(DdtRow $ddtRow): static
    {
        if (!$this->ddtRows->contains($ddtRow)) {
            $this->ddtRows->add($ddtRow);
            $ddtRow->setDdt($this);
        }

        return $this;
    }

    public function removeDdtRow(DdtRow $ddtRow): static
    {
        if ($this->ddtRows->removeElement($ddtRow)) {
            // set the owning side to null (unless already changed)
            if ($ddtRow->getDdt() === $this) {
                $ddtRow->setDdt(null);
            }
        }

        return $this;
    }

    public function getReason(): ?DdtReason
    {
        return $this->reason;
    }

    public function setReason(?DdtReason $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getClient(): ?Contact
    {
        return $this->client;
    }

    public function setClient(?Contact $client): static
    {
        $this->client = $client;

        return $this;
    }
}
