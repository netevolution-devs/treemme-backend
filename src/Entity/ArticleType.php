<?php

namespace App\Entity;

use App\Repository\ArticleTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleTypeRepository::class)]
class ArticleType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article_list', 'article_detail', 'article_type_list', 'article_type_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article_list', 'article_detail', 'article_type_list', 'article_type_detail'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'articleTypes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['article_list', 'article_detail', 'article_type_list', 'article_type_detail'])]
    private ?LeatherType $leather_type = null;

    #[ORM\ManyToOne(inversedBy: 'articleTypes')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['article_list', 'article_detail', 'article_type_list', 'article_type_detail'])]
    private ?ArticleClass $article_class = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(mappedBy: 'article_type', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
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

    public function getLeatherType(): ?LeatherType
    {
        return $this->leather_type;
    }

    public function setLeatherType(?LeatherType $leather_type): static
    {
        $this->leather_type = $leather_type;

        return $this;
    }

    public function getArticleClass(): ?ArticleClass
    {
        return $this->article_class;
    }

    public function setArticleClass(?ArticleClass $article_class): static
    {
        $this->article_class = $article_class;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setArticleType($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getArticleType() === $this) {
                $article->setArticleType(null);
            }
        }

        return $this;
    }
}
