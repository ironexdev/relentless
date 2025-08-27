<?php

declare(strict_types=1);

namespace App\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait ActiveTrait
{
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $active = true {
        get {
            return $this->active;
        }
        set {
            $this->active = $value;
        }
    }

}