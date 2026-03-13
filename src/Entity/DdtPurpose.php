<?php

namespace App\Entity;

use App\Repository\DdtPurposeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DdtPurposeRepository::class)]
class DdtPurpose
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ddt_list', 'ddt_detail', 'ddt_purpose_list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'ddtPurposes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ddt_list', 'ddt_detail', 'ddt_purpose_list'])]
    private ?self $ddt_purpose = null;

    /**
     * @var Collection<int, Ddt>
     */
    #[ORM\OneToMany(mappedBy: 'ddt_purpose', targetEntity: Ddt::class)]
    private Collection $ddts;

    public function __construct()
    {
        $this->ddts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDdtPurpose(): ?self
    {
        return $this->ddt_purpose;
    }

    public function setDdtPurpose(?self $ddt_purpose): static
    {
        $this->ddt_purpose = $ddt_purpose;

        return $this;
    }

    /**
     * @return Collection<int, Ddt>
     */
    public function getDdts(): Collection
    {
        return $this->ddts;
    }

    public function addDdt(Ddt $ddt): static
    {
        if (!$this->ddts->contains($ddt)) {
            $this->ddts->add($ddt);
            $ddt->setDdtPurpose($this);
        }

        return $this;
    }

    public function removeDdt(Ddt $ddt): static
    {
        if ($this->ddts->removeElement($ddt)) {
            // set the owning side to null (unless already changed)
            if ($ddt->getDdtPurpose() === $this) {
                $ddt->setDdtPurpose(null);
            }
        }

        return $this;
    }

}
