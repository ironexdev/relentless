<?php

declare(strict_types=1);

namespace App\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait UpdatedAtTrait
{
    #[ORM\Column(nullable: false)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestampOnPersistOrUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
