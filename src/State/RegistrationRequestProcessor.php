<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\RegistrationRequestDTO;
use App\Entity\User;
use App\Entity\VerificationPIN;
use App\Enum\VerificationActionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @implements ProcessorInterface<RegistrationRequestDTO, JsonResponse>
 */
final readonly class RegistrationRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private PasswordHasherFactoryInterface $hasherFactory,
        private string $mailerFrom,
    ) {
    }

    /**
     * @param RegistrationRequestDTO $data
     *
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);

        // Registration attempt with already existing account
        if ($existingUser) {
            $email = new Email()
                ->from($this->mailerFrom)
                ->to($data->email)
                ->subject('Registration Attempt on Your Account')
                ->html('<p>Someone just tried to create an account with your email address. If this was not you, you can safely ignore this email.</p>');

            $this->mailer->send($email);

            return new JsonResponse(['message' => 'If an account does not already exist, a registration PIN will be sent.'], Response::HTTP_OK);
        }

        $previousPin = $this->entityManager->getRepository(VerificationPIN::class)->findOneBy(['email' => $data->email]);
        if ($previousPin) {
            $this->entityManager->remove($previousPin);
            $this->entityManager->flush();
        }

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hasher = $this->hasherFactory->getPasswordHasher(VerificationPIN::class);
        $hashedPin = $hasher->hash($pin);
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $verificationPin = new VerificationPIN($data->email, $hashedPin, $expiresAt, VerificationActionEnum::REGISTRATION);
        $this->entityManager->persist($verificationPin);
        $this->entityManager->flush();

        $email = new Email()
            ->from($this->mailerFrom)
            ->to($data->email)
            ->subject('Your Registration PIN')
            ->html(sprintf('<p>Your registration PIN is: <strong>%s</strong></p><p>It will expire in 10 minutes.</p>', $pin));

        $this->mailer->send($email);

        return new JsonResponse(['message' => 'If an account does not already exist, a registration PIN will be sent.'], Response::HTTP_OK);
    }
}
