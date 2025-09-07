<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken implements RefreshTokenInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    protected ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 128, unique: true, nullable: false)]
    protected ?string $refreshToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $username = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    protected ?\DateTimeInterface $valid = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'refreshTokens')]
    #[ORM\JoinColumn(nullable: false)]
    protected User $user;

    /**
     * @throws \DateMalformedStringException
     */
    public static function createForUserWithTtl(string $refreshToken, UserInterface $user, int $ttl): RefreshTokenInterface
    {
        /** @var User $user */
        $token = new self();
        $token->setUser($user);
        $token->setUsername($user->getUserIdentifier());
        $token->setRefreshToken($refreshToken);
        $token->setValid(new \DateTime(sprintf('+%d seconds', $ttl)));

        return $token;
    }

    public function getId(): ?string
    {
        return $this->id?->toString();
    }

    public function setRefreshToken($refreshToken = null): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setValid($valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getValid(): ?\DateTimeInterface
    {
        return $this->valid;
    }

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function isValid(): bool
    {
        return null !== $this->valid && $this->valid >= new \DateTime();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function __toString(): string
    {
        return $this->getRefreshToken() ?? '';
    }
}
