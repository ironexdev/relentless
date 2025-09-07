<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\DTO\RegistrationRequestDTO;
use App\DTO\RegistrationVerifyDTO;
use App\State\RegistrationRequestProcessor;
use App\State\RegistrationVerifyProcessor;

#[ApiResource(
    shortName: 'Registration',
    operations: [
        new Post(
            uriTemplate: '/registration/request',
            openapi: new Model\Operation(
                summary: 'Request a registration PIN.',
                description: 'Sends a 6-digit PIN to the user\'s email for account creation.',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => new Model\MediaType(
                            schema: new \ArrayObject([
                                'type' => 'object',
                                'properties' => [
                                    'email' => new \ArrayObject(['type' => 'string', 'example' => 'test@example.com']),
                                ],
                            ])
                        ),
                    ])
                )
            ),
            input: RegistrationRequestDTO::class,
            processor: RegistrationRequestProcessor::class
        ),
        new Post(
            uriTemplate: '/registration/verify',
            openapi: new Model\Operation(
                summary: 'Verify a registration PIN and create a new user.',
                description: 'Verifies the PIN and creates the user account if valid.'
            ),
            input: RegistrationVerifyDTO::class,
            processor: RegistrationVerifyProcessor::class
        ),
    ]
)]
class Registration
{
    // This class is a placeholder for the API operations.
}
