<?php

namespace App\Entity;

use App\Repository\ContactAgentRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ContactAgentRepository::class)]
class ContactAgent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactAgents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_list','contact_detail','contact_client','contact_supplier', 'client_summary_print'])]
    #[MaxDepth(1)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'contactAgents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_list','contact_detail','contact_client','contact_supplier', 'client_summary_print'])]
    #[MaxDepth(1)]
    private ?Contact $agent = null;

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

    public function getAgent(): ?Contact
    {
        return $this->agent;
    }

    public function setAgent(?Contact $agent): static
    {
        $this->agent = $agent;

        return $this;
    }
}
