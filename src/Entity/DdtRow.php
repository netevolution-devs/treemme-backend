<?php

namespace App\Entity;

use App\Repository\DdtRowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DdtRowRepository::class)]
class DdtRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $order_note = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Column]
    private ?int $pieces = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRows')]
    private ?MeasurementUnit $measurement_unit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNote(): ?string
    {
        return $this->order_note;
    }

    public function setOrderNote(?string $order_note): static
    {
        $this->order_note = $order_note;

        return $this;
    }

    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    public function setBatch(?Batch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getPieces(): ?int
    {
        return $this->pieces;
    }

    public function setPieces(int $pieces): static
    {
        $this->pieces = $pieces;

        return $this;
    }

    public function getMeasurementUnit(): ?MeasurementUnit
    {
        return $this->measurement_unit;
    }

    public function setMeasurementUnit(?MeasurementUnit $measurement_unit): static
    {
        $this->measurement_unit = $measurement_unit;

        return $this;
    }
}
