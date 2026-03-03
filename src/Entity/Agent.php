<?php

namespace App\Entity;

use App\Repository\AgentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
class Agent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['agent_list', 'agent_detail', 'contact_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['agent_list', 'agent_detail', 'contact_detail'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['agent_list', 'agent_detail'])]
    private ?float $agent_percentage = null;

    #[ORM\ManyToOne(inversedBy: 'agents')]
    #[Groups(['agent_detail'])]
    private ?ContactAddress $address = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['agent_detail'])]
    private ?string $note = null;

    #[ORM\ManyToOne(inversedBy: 'agents')]
    #[Groups(['agent_detail'])]
    private ?Payment $payment = null;

    /**
     * @var Collection<int, ContactAgent>
     */
    #[ORM\OneToMany(mappedBy: 'agent', targetEntity: ContactAgent::class, orphanRemoval: true)]
    private Collection $contactAgents;

    public function __construct()
    {
        $this->contactAgents = new ArrayCollection();
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

    public function getAgentPercentage(): ?float
    {
        return $this->agent_percentage;
    }

    public function setAgentPercentage(?float $agent_percentage): static
    {
        $this->agent_percentage = $agent_percentage;

        return $this;
    }

    public function getAddress(): ?ContactAddress
    {
        return $this->address;
    }

    public function setAddress(?ContactAddress $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @return Collection<int, ContactAgent>
     */
    public function getContactAgents(): Collection
    {
        return $this->contactAgents;
    }

    public function addContactAgent(ContactAgent $contactAgent): static
    {
        if (!$this->contactAgents->contains($contactAgent)) {
            $this->contactAgents->add($contactAgent);
            $contactAgent->setAgent($this);
        }

        return $this;
    }

    public function removeContactAgent(ContactAgent $contactAgent): static
    {
        if ($this->contactAgents->removeElement($contactAgent)) {
            // set the owning side to null (unless already changed)
            if ($contactAgent->getAgent() === $this) {
                $contactAgent->setAgent(null);
            }
        }

        return $this;
    }
}
