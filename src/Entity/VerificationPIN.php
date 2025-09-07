<?php

namespace App\Entity;

use App\Enum\VerificationActionEnum;
use App\Repository\VerificationPINRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VerificationPINRepository::class)]
class VerificationPIN
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    public string $email;

    /**
     * The hashed PIN code.
     */
    #[ORM\Column(length: 255)]
    public string $hashedPin;

    #[ORM\Column]
    public \DateTimeImmutable $expiresAt;

    #[ORM\Column(enumType: VerificationActionEnum::class)]
    public VerificationActionEnum $action;

    public function __construct(string $email, string $hashedPin, \DateTimeImmutable $expiresAt, VerificationActionEnum $action)
    {
        $this->email = $email;
        $this->hashedPin = $hashedPin;
        $this->expiresAt = $expiresAt;
        $this->action = $action;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }
}
