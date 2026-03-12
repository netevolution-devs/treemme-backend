<?php

namespace App\Entity;

use App\Repository\ContactSubcontractorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactSubcontractorRepository::class)]
class ContactSubcontractor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactSubcontractors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'subcontractorContacts')]
    #[ORM\JoinColumn(nullable: false)]
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
