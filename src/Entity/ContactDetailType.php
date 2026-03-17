<?php

namespace App\Entity;

use App\Repository\ContactDetailTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactDetailTypeRepository::class)]
class ContactDetailType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact_detail_type_list','contact_detail_type_detail','contact_detail_detail','contact_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact_detail_type_list','contact_detail_type_detail','contact_detail_detail','contact_detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['contact_detail_type_list','contact_detail_type_detail','contact_detail'])]
    private ?string $code = null;

    /**
     * @var Collection<int, ContactDetail>
     */
    #[ORM\OneToMany(mappedBy: 'detail_type', targetEntity: ContactDetail::class)]
    #[Groups(['contact_detail_type_detail'])]
    private Collection $contactDetails;

    public function __construct()
    {
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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
            $contactDetail->setDetailType($this);
        }

        return $this;
    }

    public function removeContactDetail(ContactDetail $contactDetail): static
    {
        if ($this->contactDetails->removeElement($contactDetail)) {
            // set the owning side to null (unless already changed)
            if ($contactDetail->getDetailType() === $this) {
                $contactDetail->setDetailType(null);
            }
        }

        return $this;
    }
}
