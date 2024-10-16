<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $trip_type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $destination = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['list', 'detail'])]
    private ?\DateTimeInterface $trip_date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $user_name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $contractor_name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $contractor_address = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $contractor_fiscal_code = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['list', 'detail'])]
    private ?int $adults_number = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['list', 'detail'])]
    private ?int $children_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $participants_names = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $lunch = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $pref_bus_seat = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $start_point = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['detail'])]
    private ?string $payment_type = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['detail'])]
    private ?string $case_number = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['detail'])]
    private ?string $case_note = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['detail'])]
    private ?string $value = null;

    #[ORM\Column]
    #[Groups(['list', 'detail'])]
    private ?bool $email_sent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['list', 'detail'])]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['detail'])]
    private ?bool $ok = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['detail'])]
    private ?string $payment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['detail'])]
    private ?\DateTimeInterface $payment_date = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Groups(['detail'])]
    private ?string $ip = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['detail'])]
    private ?bool $execute_error = null;

    #[ORM\Column]
    #[Groups(['list', 'detail'])]
    private ?bool $vis = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $shop_id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['list', 'detail'])]
    private ?string $uni_link = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTripType(): ?string
    {
        return $this->trip_type;
    }

    public function setTripType(string $trip_type): static
    {
        $this->trip_type = $trip_type;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getTripDate(): ?\DateTimeInterface
    {
        return $this->trip_date;
    }

    public function setTripDate(\DateTimeInterface $trip_date): static
    {
        $this->trip_date = $trip_date;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }

    public function setUserName(string $user_name): static
    {
        $this->user_name = $user_name;

        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getContractorName(): ?string
    {
        return $this->contractor_name;
    }

    public function setContractorName(?string $contractor_name): static
    {
        $this->contractor_name = $contractor_name;

        return $this;
    }

    public function getContractorAddress(): ?string
    {
        return $this->contractor_address;
    }

    public function setContractorAddress(string $contractor_address): static
    {
        $this->contractor_address = $contractor_address;

        return $this;
    }

    public function getContractorFiscalCode(): ?string
    {
        return $this->contractor_fiscal_code;
    }

    public function setContractorFiscalCode(string $contractor_fiscal_code): static
    {
        $this->contractor_fiscal_code = $contractor_fiscal_code;

        return $this;
    }

    public function getAdultsNumber(): ?int
    {
        return $this->adults_number;
    }

    public function setAdultsNumber(?int $adults_number): static
    {
        $this->adults_number = $adults_number;

        return $this;
    }

    public function getChildrenNumber(): ?int
    {
        return $this->children_number;
    }

    public function setChildrenNumber(?int $children_number): static
    {
        $this->children_number = $children_number;

        return $this;
    }

    public function getParticipantsNames(): ?string
    {
        return $this->participants_names;
    }

    public function setParticipantsNames(?string $participants_names): static
    {
        $this->participants_names = $participants_names;

        return $this;
    }

    public function getLunch(): ?string
    {
        return $this->lunch;
    }

    public function setLunch(?string $lunch): static
    {
        $this->lunch = $lunch;

        return $this;
    }

    public function getPrefBusSeat(): ?string
    {
        return $this->pref_bus_seat;
    }

    public function setPrefBusSeat(?string $pref_bus_seat): static
    {
        $this->pref_bus_seat = $pref_bus_seat;

        return $this;
    }

    public function getStartPoint(): ?string
    {
        return $this->start_point;
    }

    public function setStartPoint(?string $start_point): static
    {
        $this->start_point = $start_point;

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

    public function getPaymentType(): ?string
    {
        return $this->payment_type;
    }

    public function setPaymentType(?string $payment_type): static
    {
        $this->payment_type = $payment_type;

        return $this;
    }

    public function getCaseNumber(): ?string
    {
        return $this->case_number;
    }

    public function setCaseNumber(?string $case_number): static
    {
        $this->case_number = $case_number;

        return $this;
    }

    public function getCaseNote(): ?string
    {
        return $this->case_note;
    }

    public function setCaseNote(?string $case_note): static
    {
        $this->case_note = $case_note;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function isEmailSent(): ?bool
    {
        return $this->email_sent;
    }

    public function setEmailSent(bool $email_sent): static
    {
        $this->email_sent = $email_sent;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function isOk(): ?bool
    {
        return $this->ok;
    }

    public function setOk(?bool $ok): static
    {
        $this->ok = $ok;

        return $this;
    }

    public function getPayment(): ?string
    {
        return $this->payment;
    }

    public function setPayment(?string $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(?\DateTimeInterface $payment_date): static
    {
        $this->payment_date = $payment_date;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function isExecuteError(): ?bool
    {
        return $this->execute_error;
    }

    public function setExecuteError(?bool $execute_error): static
    {
        $this->execute_error = $execute_error;

        return $this;
    }

    public function isVis(): ?bool
    {
        return $this->vis;
    }

    public function setVis(bool $vis): static
    {
        $this->vis = $vis;

        return $this;
    }

    public function getShopId(): ?string
    {
        return $this->shop_id;
    }

    public function setShopId(string $shop_id): static
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function getUniLink(): ?string
    {
        return $this->uni_link;
    }

    public function setUniLink(string $uni_link): static
    {
        $this->uni_link = $uni_link;

        return $this;
    }
}
