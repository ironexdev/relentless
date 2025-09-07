<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\LoginVerifyDTO;
use App\DTO\TokenResponseDTO;
use App\Entity\User;
use App\Entity\VerificationPIN;
use App\Enum\VerificationActionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @implements ProcessorInterface<LoginVerifyDTO, TokenResponseDTO>
 */
final readonly class LoginVerifyProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordHasherFactoryInterface $hasherFactory,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $refreshTokenTtl,
    ) {
    }

    /**
     * @param LoginVerifyDTO $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenResponseDTO
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);
        if (!$user) {
            throw new BadRequestHttpException('User with this email not found.');
        }

        $pinRecord = $this->entityManager->getRepository(VerificationPIN::class)->findOneBy(['email' => $data->email]);
        if (!$pinRecord || VerificationActionEnum::LOGIN !== $pinRecord->action) {
            throw new BadRequestHttpException('No login process started for this email. Please request a PIN first.');
        }

        if ($pinRecord->isExpired()) {
            $this->entityManager->remove($pinRecord);
            $this->entityManager->flush();
            throw new BadRequestHttpException('The login PIN has expired. Please request a new one.');
        }

        $hasher = $this->hasherFactory->getPasswordHasher(VerificationPIN::class);
        if (!$hasher->verify($pinRecord->hashedPin, $data->pin)) {
            throw new BadRequestHttpException('The provided PIN is incorrect.');
        }

        $this->entityManager->remove($pinRecord);
        $this->entityManager->flush();

        $accessToken = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return new TokenResponseDTO($accessToken, $refreshToken->getRefreshToken());
    }
}
