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
    private ?Group $group = null;

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

    #[ORM\Column(options: ["default" => true])]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private bool $canGet = true;

    #[ORM\Column(options: ["default" => false])]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private bool $canPost = false;

    #[ORM\Column(options: ["default" => false])]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private bool $canPut = false;

    #[ORM\Column(options: ["default" => false])]
    #[Groups(['group_role_work_area_list', 'group_role_work_area_detail', 'group_detail'])]
    private bool $canDelete = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;

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

    public function isCanGet(): bool
    {
        return $this->canGet;
    }

    public function setCanGet(bool $canGet): static
    {
        $this->canGet = $canGet;

        return $this;
    }

    public function isCanPost(): bool
    {
        return $this->canPost;
    }

    public function setCanPost(bool $canPost): static
    {
        $this->canPost = $canPost;

        return $this;
    }

    public function isCanPut(): bool
    {
        return $this->canPut;
    }

    public function setCanPut(bool $canPut): static
    {
        $this->canPut = $canPut;

        return $this;
    }

    public function isCanDelete(): bool
    {
        return $this->canDelete;
    }

    public function setCanDelete(bool $canDelete): static
    {
        $this->canDelete = $canDelete;

        return $this;
    }
}
