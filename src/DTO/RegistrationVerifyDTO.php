<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationVerifyDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 320)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 6)]
    #[Assert\Regex(pattern: '/^[0-9]+$/')]
    public string $pin;
}
