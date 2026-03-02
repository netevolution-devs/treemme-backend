<?php

namespace App\Entity;

use App\Repository\ContactAgentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactAgentRepository::class)]
class ContactAgent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactAgents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'contactAgents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agent $agent = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): static
    {
        $this->agent = $agent;

        return $this;
    }
}
