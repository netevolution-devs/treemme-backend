<?php

namespace App\Entity;

use App\Repository\ContactDetailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactDetailRepository::class)]
class ContactDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_detail_list','contact_detail_detail','contact_detail','contact_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact_detail_list','contact_detail_detail','contact_detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_detail_list','contact_detail_detail','contact_detail'])]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['contact_detail_list','contact_detail_detail'])]
    private ?int $weight = null;

    #[ORM\ManyToOne(inversedBy: 'contactDetails')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contact_detail_list','contact_detail_detail'])]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'contactDetails')]
    #[Groups(['contact_detail_list','contact_detail_detail'])]
    private ?ContactDetailType $detail_type = null;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

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

    public function getDetailType(): ?ContactDetailType
    {
        return $this->detail_type;
    }

    public function setDetailType(?ContactDetailType $detail_type): static
    {
        $this->detail_type = $detail_type;

        return $this;
    }
}
