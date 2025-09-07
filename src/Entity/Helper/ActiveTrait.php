<?php

declare(strict_types=1);

namespace App\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait ActiveTrait
{
    #[ORM\Column(options: ['default' => false])]
    public bool $active = false {
        get => $this->active;
        set => (bool) $value;
    }
}
