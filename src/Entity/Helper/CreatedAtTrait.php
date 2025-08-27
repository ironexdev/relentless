<?php

declare(strict_types=1);

namespace App\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    #[ORM\Column(nullable: false)]
    private ?\DateTimeImmutable $createdAt;

    public function getCreatedAt(): \DateTimeImmutable
    {
        if (null === $this->createdAt) {
            throw new \RuntimeException('This entity has not been persisted yet.');
        }

        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
