<?php

namespace App\Entity;

use App\Repository\ContactSubcontractorRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ContactSubcontractorRepository::class)]
class ContactSubcontractor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_list','contact_detail','contact_client','contact_supplier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactSubcontractors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_list','contact_detail','contact_client','contact_supplier'])]
    #[MaxDepth(1)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'subcontractorContacts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_list','contact_detail','contact_client','contact_supplier'])]
    #[MaxDepth(1)]
    private ?Contact $subcontractor = null;

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

    public function getSubcontractor(): ?Contact
    {
        return $this->subcontractor;
    }

    public function setSubcontractor(?Contact $subcontractor): static
    {
        $this->subcontractor = $subcontractor;

        return $this;
    }
}
