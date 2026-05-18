<?php

namespace App\Entity;

use App\Repository\WorkAreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WorkAreaRepository::class)]
class WorkArea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['work_area_list','work_area_detail','group_role_work_area_list','group_role_work_area_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['work_area_list','work_area_detail', 'group_role_work_area_list', 'group_role_work_area_detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['work_area_detail', 'group_role_work_area_detail'])]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, GroupRoleWorkArea>
     */
    #[ORM\OneToMany(mappedBy: 'workArea', targetEntity: GroupRoleWorkArea::class)]
    #[Groups(['work_area_list', 'work_area_detail'])]
    private Collection $groupRoleWorkAreas;

    public function __construct()
    {
        $this->groupRoleWorkAreas = new ArrayCollection();
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
     * @return Collection<int, GroupRoleWorkArea>
     */
    public function getGroupRoleWorkAreas(): Collection
    {
        return $this->groupRoleWorkAreas;
    }

    public function addGroupRoleWorkArea(GroupRoleWorkArea $groupRoleWorkArea): static
    {
        if (!$this->groupRoleWorkAreas->contains($groupRoleWorkArea)) {
            $this->groupRoleWorkAreas->add($groupRoleWorkArea);
            $groupRoleWorkArea->setWorkArea($this);
        }

        return $this;
    }

    public function removeGroupRoleWorkArea(GroupRoleWorkArea $groupRoleWorkArea): static
    {
        if ($this->groupRoleWorkAreas->removeElement($groupRoleWorkArea)) {
            // set the owning side to null (unless already changed)
            if ($groupRoleWorkArea->getWorkArea() === $this) {
                $groupRoleWorkArea->setWorkArea(null);
            }
        }

        return $this;
    }
}
