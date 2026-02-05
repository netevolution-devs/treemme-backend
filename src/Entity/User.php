<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user_list','user_detail','group_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user_list','user_detail','group_detail'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $totpEnabled = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $totpEnabledAt = null;

    #[ORM\Column]
    #[Groups(['user_list','user_detail'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user_list','user_detail','group_detail'])]
    private ?string $user_code = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user_list','user_detail','group_detail'])]
    private ?\DateTimeImmutable $last_access = null;

    /**
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(mappedBy: 'check_user', targetEntity: Batch::class)]
    private Collection $batches;

    /**
     * @var Collection<int, GroupUser>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: GroupUser::class, orphanRemoval: true)]
    private Collection $groupUsers;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
        $this->groupUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }

    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
    }

    public function setTotpEnabled(bool $totpEnabled): self
    {
        $this->totpEnabled = $totpEnabled;
        return $this;
    }

    public function getTotpEnabledAt(): ?\DateTimeImmutable
    {
        return $this->totpEnabledAt;
    }

    public function setTotpEnabledAt(?\DateTimeImmutable $totpEnabledAt): self
    {
        $this->totpEnabledAt = $totpEnabledAt;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUserCode(): ?string
    {
        return $this->user_code;
    }

    public function setUserCode(string $user_code): static
    {
        $this->user_code = $user_code;

        return $this;
    }

    public function getLastAccess(): ?\DateTimeImmutable
    {
        return $this->last_access;
    }

    public function setLastAccess(?\DateTimeImmutable $last_access): static
    {
        $this->last_access = $last_access;

        return $this;
    }

    /**
     * @return Collection<int, Batch>
     */
    public function getBatches(): Collection
    {
        return $this->batches;
    }

    public function addBatch(Batch $batch): static
    {
        if (!$this->batches->contains($batch)) {
            $this->batches->add($batch);
            $batch->setCheckUser($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getCheckUser() === $this) {
                $batch->setCheckUser(null);
            }
        }

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
            $groupUser->setUser($this);
        }

        return $this;
    }

    public function removeGroupUser(GroupUser $groupUser): static
    {
        if ($this->groupUsers->removeElement($groupUser)) {
            // set the owning side to null (unless already changed)
            if ($groupUser->getUser() === $this) {
                $groupUser->setUser(null);
            }
        }

        return $this;
    }
}
