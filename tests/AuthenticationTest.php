<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Entity\VerificationPIN;
use App\Enum\VerificationActionEnum;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AuthenticationTest extends ApiTestCase
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testRequestRegistrationPinSuccess(): void
    {
        $client = static::createClient();
        $email = 'new-user@example.com';

        $client->request('POST', '/api/registration/request', [
            'json' => ['email' => $email],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['message' => 'If an account does not already exist, a registration PIN will be sent.']);
        $this->assertEmailCount(1);
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $this->assertEmailHeaderSame($emailMessage, 'To', $email);
        $this->assertEmailHtmlBodyContains($emailMessage, 'Your registration PIN is:');

        $pinRecord = $this->findVerificationPinForEmail($email);
        $this->assertNotNull($pinRecord);
        $this->assertEquals(VerificationActionEnum::REGISTRATION, $pinRecord->action);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testRequestRegistrationPinForExistingUserSendsNotification(): void
    {
        $client = static::createClient();
        $email = 'existing-user@example.com';
        $this->createTestUser($email);

        $client->request('POST', '/api/registration/request', [
            'json' => ['email' => $email],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $this->assertEmailHeaderSame($emailMessage, 'To', $email);
        $this->assertEmailSubjectContains($emailMessage, 'Registration Attempt on Your Account');

        $pinRecord = $this->findVerificationPinForEmail($email);
        $this->assertNull($pinRecord);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testVerifyRegistrationSuccess(): void
    {
        $client = static::createClient();
        $email = 'verify-user@example.com';

        $client->request('POST', '/api/registration/request', ['json' => ['email' => $email]]);
        $this->assertResponseIsSuccessful();
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $pin = $this->extractPinFromEmail($emailMessage);

        $response = $client->request('POST', '/api/registration/verify', [
            'json' => [
                'email' => $email,
                'pin' => $pin,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@type' => 'TokenResponseDTO']);
        $data = $response->toArray();
        $this->assertArrayHasKey('accessToken', $data);
        $this->assertArrayHasKey('refreshToken', $data);
        $this->assertNotNull($this->findUserByEmail($email));
        $this->assertNull($this->findVerificationPinForEmail($email));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testRequestLoginPinSuccess(): void
    {
        $client = static::createClient();
        $email = 'login-user@example.com';
        $this->createTestUser($email);

        $client->request('POST', '/api/login/request', [
            'json' => ['email' => $email],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['message' => 'If an account exists for this email, a login PIN will be sent.']);
        $this->assertEmailCount(1);
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $this->assertEmailHeaderSame($emailMessage, 'To', $email);
        $this->assertEmailHtmlBodyContains($emailMessage, 'Your login PIN is:');

        $pinRecord = $this->findVerificationPinForEmail($email);
        $this->assertNotNull($pinRecord);
        $this->assertEquals(VerificationActionEnum::LOGIN, $pinRecord->action);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testRequestLoginPinForNonExistentUserSendsNotification(): void
    {
        $client = static::createClient();
        $email = 'non-existent-user@example.com';

        $client->request('POST', '/api/login/request', [
            'json' => ['email' => $email],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $this->assertEmailHeaderSame($emailMessage, 'To', $email);
        $this->assertEmailSubjectContains($emailMessage, 'Login Attempt on an Unregistered Account');
        $this->assertNull($this->findVerificationPinForEmail($email));
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testVerifyLoginSuccess(): void
    {
        $client = static::createClient();
        $email = 'final-login-user@example.com';
        $this->createTestUser($email);

        $client->request('POST', '/api/login/request', ['json' => ['email' => $email]]);
        $this->assertResponseIsSuccessful();
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $pin = $this->extractPinFromEmail($emailMessage);

        $response = $client->request('POST', '/api/login/verify', [
            'json' => [
                'email' => $email,
                'pin' => $pin,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@type' => 'TokenResponseDTO']);
        $data = $response->toArray();
        $this->assertArrayHasKey('accessToken', $data);
        $this->assertArrayHasKey('refreshToken', $data);
        $this->assertNull($this->findVerificationPinForEmail($email));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testVerifyLoginFailsWithIncorrectPin(): void
    {
        $client = static::createClient();
        $email = 'bad-pin-user@example.com';
        $this->createTestUser($email);

        $client->request('POST', '/api/login/request', ['json' => ['email' => $email]]);

        $client->request('POST', '/api/login/verify', [
            'json' => [
                'email' => $email,
                'pin' => '000000',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['detail' => 'The provided PIN is incorrect.']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testVerifyLoginFailsWithRegistrationPin(): void
    {
        $client = static::createClient();
        $email = 'wrong-pin-type@example.com';

        // Request a REGISTRATION pin
        $client->request('POST', '/api/registration/request', ['json' => ['email' => $email]]);
        $emailMessage = $this->getMailerMessage();
        $this->assertNotNull($emailMessage);
        $pin = $this->extractPinFromEmail($emailMessage);

        // But try to use it for LOGIN
        $client->request('POST', '/api/login/verify', [
            'json' => [
                'email' => $email,
                'pin' => $pin,
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['detail' => 'User with this email not found.']);
    }

    private function createTestUser(string $email): User
    {
        $user = new User();
        $user->email = $email;
        $user->active = true;
        $user->setRoles(['ROLE_USER']);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function findUserByEmail(string $email): ?User
    {
        return static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    private function findVerificationPinForEmail(string $email): ?VerificationPIN
    {
        return static::getContainer()->get('doctrine')->getRepository(VerificationPIN::class)->findOneBy(['email' => $email]);
    }

    private function extractPinFromEmail(RawMessage $email): ?string
    {
        if (!$email instanceof Email) {
            return null;
        }

        if (preg_match('/<strong>(\d{6})<\/strong>/', $email->getHtmlBody(), $matches)) {
            return $matches[1];
        }

        return null;
    }
}
