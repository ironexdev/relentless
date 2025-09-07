<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\RegistrationVerifyDTO;
use App\DTO\TokenResponseDTO;
use App\Entity\User;
use App\Entity\VerificationPIN;
use App\Enum\VerificationActionEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @implements ProcessorInterface<RegistrationVerifyDTO, TokenResponseDTO>
 */
final readonly class RegistrationVerifyProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $refreshTokenTtl,
    ) {
    }

    /**
     * @param RegistrationVerifyDTO $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenResponseDTO
    {
        $pinRecord = $this->entityManager->getRepository(VerificationPIN::class)->findOneBy(['email' => $data->email]);

        if (!$pinRecord || VerificationActionEnum::REGISTRATION !== $pinRecord->action) {
            throw new BadRequestHttpException('No registration process started for this email.');
        }

        if ($pinRecord->isExpired()) {
            $this->entityManager->remove($pinRecord);
            $this->entityManager->flush();
            throw new BadRequestHttpException('The registration PIN has expired. Please request a new one.');
        }

        $hasher = $this->hasherFactory->getPasswordHasher(VerificationPIN::class);
        if (!$hasher->verify($pinRecord->hashedPin, $data->pin)) {
            throw new BadRequestHttpException('The provided PIN is incorrect.');
        }

        if ($this->userRepository->findOneBy(['email' => $data->email])) {
            throw new BadRequestHttpException('A user with this email already exists.');
        }

        $user = new User();
        $user->email = $data->email;
        $user->active = true;
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->remove($pinRecord);
        $this->entityManager->flush();

        $accessToken = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return new TokenResponseDTO($accessToken, $refreshToken->getRefreshToken());
    }
}
