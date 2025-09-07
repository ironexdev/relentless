<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\LoginRequestDTO;
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
 * @implements ProcessorInterface<LoginRequestDTO, JsonResponse>
 */
final readonly class LoginRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private PasswordHasherFactoryInterface $hasherFactory,
        private string $mailerFrom,
    ) {
    }

    /**
     * @param LoginRequestDTO $data
     *
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);

        // Login attempt with unregistered email
        if (!$user) {
            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($data->email)
                ->subject('Login Attempt on an Unregistered Account')
                ->html('<p>Someone just tried to log in with your email address, but no account exists. If this was you, please register first. If not, you can safely ignore this email.</p>');

            $this->mailer->send($email);

            return new JsonResponse(['message' => 'If an account exists for this email, a login PIN will be sent.'], Response::HTTP_OK);
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

        $verificationPin = new VerificationPIN($data->email, $hashedPin, $expiresAt, VerificationActionEnum::LOGIN);
        $this->entityManager->persist($verificationPin);
        $this->entityManager->flush();

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($data->email)
            ->subject('Your Login PIN')
            ->html(sprintf('<p>Your login PIN is: <strong>%s</strong></p><p>It will expire in 10 minutes.</p>', $pin));

        $this->mailer->send($email);

        return new JsonResponse(['message' => 'If an account exists for this email, a login PIN will be sent.'], Response::HTTP_OK);
    }
}
