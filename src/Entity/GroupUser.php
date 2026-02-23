<?php

namespace App\Entity;

use App\Repository\GroupUserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupUserRepository::class)]
class GroupUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group_user_list', 'group_user_detail', 'group_detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'groupUsers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group_user_list', 'group_user_detail'])]
    private ?Group $groupp = null;

    #[ORM\ManyToOne(inversedBy: 'groupUsers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group_user_list', 'group_user_detail', 'group_list', 'group_detail'])]
    private ?User $user = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
