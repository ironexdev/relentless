<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Helper\ActiveTrait;
use App\Entity\Helper\CreatedAtTrait;
use App\Entity\Helper\PrimaryKeyTrait;
use App\Entity\Helper\UpdatedAtTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
class User implements UserInterface
{
    use PrimaryKeyTrait;
    use CreatedAtTrait;
    use UpdatedAtTrait;
    use ActiveTrait;

    #[ORM\Column(length: 320, unique: true)]
    #[Groups(['user:read'])]
    public string $email;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['user:read'])]
    public ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var Collection<int, RefreshToken>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RefreshToken::class, cascade: ['persist', 'remove'])]
    private Collection $refreshTokens;

    public function __construct()
    {
        $this->refreshTokens = new ArrayCollection();
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, RefreshToken>
     */
    public function getRefreshTokens(): Collection
    {
        return $this->refreshTokens;
    }

    public function addRefreshToken(RefreshToken $refreshToken): static
    {
        if (!$this->refreshTokens->contains($refreshToken)) {
            $this->refreshTokens->add($refreshToken);
            $refreshToken->setUser($this);
        }

        return $this;
    }

    public function removeRefreshToken(RefreshToken $refreshToken): static
    {
        $this->refreshTokens->removeElement($refreshToken);

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
