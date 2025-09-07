<?php

declare(strict_types=1);

namespace App\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    #[ORM\Column(nullable: false)]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function initializeCreatedAtOnPersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
