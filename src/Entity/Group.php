<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, GroupUser>
     */
    #[ORM\OneToMany(mappedBy: 'groupp', targetEntity: GroupUser::class, orphanRemoval: true)]
    private Collection $groupUsers;

    public function __construct()
    {
        $this->groupUsers = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
     * @return Collection<int, GroupUser>
     */
    public function getGroupUsers(): Collection
    {
        return $this->groupUsers;
    }

    public function addGroupUser(GroupUser $groupUser): static
    {
        if (!$this->groupUsers->contains($groupUser)) {
            $this->groupUsers->add($groupUser);
            $groupUser->setGroupp($this);
        }

        return $this;
    }

    public function removeGroupUser(GroupUser $groupUser): static
    {
        if ($this->groupUsers->removeElement($groupUser)) {
            // set the owning side to null (unless already changed)
            if ($groupUser->getGroupp() === $this) {
                $groupUser->setGroupp(null);
            }
        }

        return $this;
    }
}
