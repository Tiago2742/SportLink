<?php

namespace App\Controller;

use App\Reference\SportNiveaux;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SportsController extends AbstractController
{
    #[Route('/api/sports', name: 'api_sports', methods: ['GET'])]
    public function catalogue(): JsonResponse
    {
        return $this->json(SportNiveaux::CATALOGUE);
    }
}
