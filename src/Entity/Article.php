<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article_list', 'article_detail', 'client_order_row_detail', 'client_order_detail',
        'batch_list', 'batch_detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail', 'client_order_row_detail', 'client_order_detail', 'batch_list', 'batch_detail'])]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article_list', 'article_detail'])]
    private ?Contact $client = null;

    #[ORM\Column]
    #[Groups(['article_list', 'article_detail'])]
    private ?bool $full_grain = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['article_list', 'article_detail'])]
    private ?ArticleType $article_type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article_list', 'article_detail'])]
    private ?string $article_variation = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['article_list', 'article_detail'])]
    private ?LeatherThickness $thickness = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article_list', 'article_detail'])]
    private ?ArticlePrint $print = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article_detail'])]
    private ?string $note = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article_list', 'article_detail'])]
    private ?ColorType $color_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail'])]
    private ?string $shade = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail'])]
    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail'])]
    private ?string $color_variation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article_detail'])]
    private ?string $color_note = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail'])]
    private ?string $client_color = null;


    /**
     * @var Collection<int, Batch>
     */
    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Batch::class)]
    private Collection $batches;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article_list', 'article_detail'])]
    private ?Product $product = null;

    /**
     * @var Collection<int, ClientOrderRow>
     */
    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ClientOrderRow::class)]
    private Collection $clientOrderRows;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article_list', 'article_detail', 'client_order_row_detail', 'client_order_detail', 'batch_list', 'batch_detail'])]
    private ?string $name = null;

    public function __construct()
    {
        $this->batches = new ArrayCollection();
        $this->clientOrderRows = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getClient(): ?Contact
    {
        return $this->client;
    }

    public function setClient(?Contact $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function isFullGrain(): ?bool
    {
        return $this->full_grain;
    }

    public function setFullGrain(bool $full_grain): static
    {
        $this->full_grain = $full_grain;

        return $this;
    }

    public function getArticleType(): ?ArticleType
    {
        return $this->article_type;
    }

    public function setArticleType(?ArticleType $article_type): static
    {
        $this->article_type = $article_type;

        return $this;
    }

    public function getArticleVariation(): ?string
    {
        return $this->article_variation;
    }

    public function setArticleVariation(string $article_variation): static
    {
        $this->article_variation = $article_variation;

        return $this;
    }

    public function getThickness(): ?LeatherThickness
    {
        return $this->thickness;
    }

    public function setThickness(?LeatherThickness $thickness): static
    {
        $this->thickness = $thickness;

        return $this;
    }

    public function getPrint(): ?ArticlePrint
    {
        return $this->print;
    }

    public function setPrint(?ArticlePrint $print): static
    {
        $this->print = $print;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getColorType(): ?ColorType
    {
        return $this->color_type;
    }

    public function setColorType(?ColorType $color_type): static
    {
        $this->color_type = $color_type;

        return $this;
    }

    public function getShade(): ?string
    {
        return $this->shade;
    }

    public function setShade(?string $shade): static
    {
        $this->shade = $shade;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColorVariation(): ?string
    {
        return $this->color_variation;
    }

    public function setColorVariation(?string $color_variation): static
    {
        $this->color_variation = $color_variation;

        return $this;
    }

    public function getColorNote(): ?string
    {
        return $this->color_note;
    }

    public function setColorNote(?string $color_note): static
    {
        $this->color_note = $color_note;

        return $this;
    }

    public function getClientColor(): ?string
    {
        return $this->client_color;
    }

    public function setClientColor(?string $client_color): static
    {
        $this->client_color = $client_color;

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
            $batch->setArticle($this);
        }

        return $this;
    }

    public function removeBatch(Batch $batch): static
    {
        if ($this->batches->removeElement($batch)) {
            // set the owning side to null (unless already changed)
            if ($batch->getArticle() === $this) {
                $batch->setArticle(null);
            }
        }

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }


    /**
     * @return Collection<int, ClientOrderRow>
     */
    public function getClientOrderRows(): Collection
    {
        return $this->clientOrderRows;
    }

    public function addClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if (!$this->clientOrderRows->contains($clientOrderRow)) {
            $this->clientOrderRows->add($clientOrderRow);
            $clientOrderRow->setArticle($this);
        }

        return $this;
    }

    public function removeClientOrderRow(ClientOrderRow $clientOrderRow): static
    {
        if ($this->clientOrderRows->removeElement($clientOrderRow)) {
            // set the owning side to null (unless already changed)
            if ($clientOrderRow->getArticle() === $this) {
                $clientOrderRow->setArticle(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
