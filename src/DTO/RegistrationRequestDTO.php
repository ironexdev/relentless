<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 320)]
    public string $email;
}
