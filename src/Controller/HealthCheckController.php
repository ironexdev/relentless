<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController
{
    #[Route('/readyz', name: 'health_check_readyz')]
    public function readyz(Connection $connection): JsonResponse
    {
        try {
            // Check if DB connection is ready
            $connection->executeQuery('SELECT 1');

            return new JsonResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }

    #[Route('/livez', name: 'health_check_livez')]
    public function livez(): JsonResponse
    {
        try {
            return new JsonResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }
}
