<?php

namespace App\Entity;

use App\Repository\DdtRowProcessingRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DdtRowProcessingRepository::class)]
class DdtRowProcessing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ddtRowProcessings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DdtRow $ddt_row = null;

    #[ORM\ManyToOne(inversedBy: 'ProcessingDdtRows')]
    #[Groups(['ddt_detail', 'ddt_row_list', 'ddt_row_detail'])]
    private ?Processing $processing = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDdtRow(): ?DdtRow
    {
        return $this->ddt_row;
    }

    public function setDdtRow(?DdtRow $ddt_row): static
    {
        $this->ddt_row = $ddt_row;

        return $this;
    }

    public function getProcessing(): ?Processing
    {
        return $this->processing;
    }

    public function setProcessing(?Processing $processing): static
    {
        $this->processing = $processing;

        return $this;
    }

}
