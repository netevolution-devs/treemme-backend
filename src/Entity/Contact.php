<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_list','contact_detail','contact_type_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact_list','contact_detail','contact_type_detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact_list','contact_detail'])]
    private ?ContactType $contact_type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['contact_detail','contact_type_detail'])]
    private ?string $contact_note = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactAddress::class, orphanRemoval: true)]
    #[Groups(['contact_list','contact_detail'])]
    private Collection $contactAddresses;

    /**
     * @var Collection<int, Supplier>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Supplier::class)]
    private Collection $suppliers;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Client::class)]
    private Collection $clients;

    /**
     * @var Collection<int, Leather>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Leather::class)]
    private Collection $leather;

    /**
     * @var Collection<int, ContactDetail>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactDetail::class, orphanRemoval: true)]
    #[Groups(['contact_list','contact_detail'])]
    private Collection $contactDetails;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[Groups(['contact_list','contact_detail'])]
    private ?ContactTitle $contact_title = null;

    public function __construct()
    {
        $this->contactAddresses = new ArrayCollection();
        $this->suppliers = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->leather = new ArrayCollection();
        $this->contactDetails = new ArrayCollection();
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

    public function getContactType(): ?ContactType
    {
        return $this->contact_type;
    }

    public function setContactType(?ContactType $contact_type): static
    {
        $this->contact_type = $contact_type;

        return $this;
    }

    public function getContactNote(): ?string
    {
        return $this->contact_note;
    }

    public function setContactNote(?string $contact_note): static
    {
        $this->contact_note = $contact_note;

        return $this;
    }

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses(): Collection
    {
        return $this->contactAddresses;
    }

    public function addContactAddress(ContactAddress $contactAddress): static
    {
        if (!$this->contactAddresses->contains($contactAddress)) {
            $this->contactAddresses->add($contactAddress);
            $contactAddress->setContact($this);
        }

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddress): static
    {
        if ($this->contactAddresses->removeElement($contactAddress)) {
            // set the owning side to null (unless already changed)
            if ($contactAddress->getContact() === $this) {
                $contactAddress->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function getSuppliers(): Collection
    {
        return $this->suppliers;
    }

    public function addSupplier(Supplier $supplier): static
    {
        if (!$this->suppliers->contains($supplier)) {
            $this->suppliers->add($supplier);
            $supplier->setContact($this);
        }

        return $this;
    }

    public function removeSupplier(Supplier $supplier): static
    {
        if ($this->suppliers->removeElement($supplier)) {
            // set the owning side to null (unless already changed)
            if ($supplier->getContact() === $this) {
                $supplier->setContact(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setContact($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getContact() === $this) {
                $client->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Leather>
     */
    public function getLeather(): Collection
    {
        return $this->leather;
    }

    public function addLeather(Leather $leather): static
    {
        if (!$this->leather->contains($leather)) {
            $this->leather->add($leather);
            $leather->setContact($this);
        }

        return $this;
    }

    public function removeLeather(Leather $leather): static
    {
        if ($this->leather->removeElement($leather)) {
            // set the owning side to null (unless already changed)
            if ($leather->getContact() === $this) {
                $leather->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactDetail>
     */
    public function getContactDetails(): Collection
    {
        return $this->contactDetails;
    }

    public function addContactDetail(ContactDetail $contactDetail): static
    {
        if (!$this->contactDetails->contains($contactDetail)) {
            $this->contactDetails->add($contactDetail);
            $contactDetail->setContact($this);
        }

        return $this;
    }

    public function removeContactDetail(ContactDetail $contactDetail): static
    {
        if ($this->contactDetails->removeElement($contactDetail)) {
            // set the owning side to null (unless already changed)
            if ($contactDetail->getContact() === $this) {
                $contactDetail->setContact(null);
            }
        }

        return $this;
    }

    public function getContactTitle(): ?ContactTitle
    {
        return $this->contact_title;
    }

    public function setContactTitle(?ContactTitle $contact_title): static
    {
        $this->contact_title = $contact_title;

        return $this;
    }
}
