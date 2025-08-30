<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController
{
    #[Route("/", name: "home")]
    public function index(): JsonResponse{
        return new JsonResponse(['foo' => 'bar']);
    }
}