<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\DTO\LoginRequestDTO;
use App\DTO\LoginVerifyDTO;
use App\State\LoginRequestProcessor;
use App\State\LoginVerifyProcessor;

#[ApiResource(
    shortName: 'Login',
    operations: [
        new Post(
            uriTemplate: '/login/request',
            openapi: new Model\Operation(
                summary: 'Request a login PIN for an existing user.',
                description: 'Sends a 6-digit PIN to the user\'s email to begin the login process.'
            ),
            input: LoginRequestDTO::class,
            processor: LoginRequestProcessor::class
        ),
        new Post(
            uriTemplate: '/login/verify',
            openapi: new Model\Operation(
                summary: 'Verify a login PIN and receive authentication tokens.',
                description: 'Verifies the PIN and returns auth tokens if valid.'
            ),
            input: LoginVerifyDTO::class,
            processor: LoginVerifyProcessor::class
        ),
    ]
)]
class Login
{
    // This class is a placeholder for the API operations.
}
