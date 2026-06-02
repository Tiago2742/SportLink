<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProfilController extends AbstractController
{
    #[Route('/api/profil', methods: ['GET'])]
    public function profil(): JsonResponse
    {
        return $this->json(
            $this->getUser(),
            200,
            [],
            ['groups' => ['utilisateur:read', 'utilisateur_sport:read']],
        );
    }
}
