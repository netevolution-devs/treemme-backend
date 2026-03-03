<?php

namespace App\Entity;

use App\Repository\BatchOrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchOrderRepository::class)]
class BatchOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'batchOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Batch $batch = null;

    #[ORM\ManyToOne(inversedBy: 'batchOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ClientOrderRow $order_row = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrderRow(): ?ClientOrderRow
    {
        return $this->order_row;
    }

    public function setOrderRow(?ClientOrderRow $order_row): static
    {
        $this->order_row = $order_row;

        return $this;
    }
}
