<?php

namespace App\Entity;

use App\Repository\GroupRoleWorkAreaRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupRoleWorkAreaRepository::class)]
class GroupRoleWorkArea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail'])]
    private ?Group $groupp = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private ?Role $role = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private ?WorkArea $workArea = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupp(): ?Group
    {
        return $this->groupp;
    }

    public function setGroupp(?Group $groupp): static
    {
        $this->groupp = $groupp;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getWorkArea(): ?WorkArea
    {
        return $this->workArea;
    }

    public function setWorkArea(?WorkArea $workArea): static
    {
        $this->workArea = $workArea;

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
}
