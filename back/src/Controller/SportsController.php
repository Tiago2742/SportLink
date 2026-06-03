<?php

namespace App\Controller;

use App\Repository\SportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SportsController extends AbstractController
{
    #[Route('/api/sports', name: 'api_sports', methods: ['GET'])]
    public function catalogue(SportRepository $sportRepo): JsonResponse
    {
        $sports = $sportRepo->findAllAvecNiveaux();

        return $this->json($sports, 200, [], ['groups' => ['sport:read', 'sport:niveaux', 'niveau:read']]);
    }
}
