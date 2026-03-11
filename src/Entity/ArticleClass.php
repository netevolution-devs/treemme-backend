<?php

namespace App\Entity;

use App\Repository\ArticleClassRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleClassRepository::class)]
class ArticleClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article_class_list', 'article_class_detail', 'article_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article_class_list', 'article_class_detail', 'article_detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article_class_list', 'article_class_detail', 'article_detail'])]
    private ?string $description = null;

    /**
     * @var Collection<int, ArticleType>
     */
    #[ORM\OneToMany(mappedBy: 'article_class', targetEntity: ArticleType::class)]
    private Collection $articleTypes;

    public function __construct()
    {
        $this->articleTypes = new ArrayCollection();
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

    /**
     * @return Collection<int, ArticleType>
     */
    public function getArticleTypes(): Collection
    {
        return $this->articleTypes;
    }

    public function addArticleType(ArticleType $articleType): static
    {
        if (!$this->articleTypes->contains($articleType)) {
            $this->articleTypes->add($articleType);
            $articleType->setArticleClass($this);
        }

        return $this;
    }

    public function removeArticleType(ArticleType $articleType): static
    {
        if ($this->articleTypes->removeElement($articleType)) {
            // set the owning side to null (unless already changed)
            if ($articleType->getArticleClass() === $this) {
                $articleType->setArticleClass(null);
            }
        }

        return $this;
    }
}
