<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginVerifyDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 6)]
    #[Assert\Regex(pattern: '/^[0-9]+$/')]
    public string $pin;
}
