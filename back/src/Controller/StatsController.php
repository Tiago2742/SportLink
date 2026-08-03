<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use App\Service\StatMatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats', methods: ['GET'])]
class StatsController extends AbstractController
{
    public function __invoke(StatMatchService $statMatchService): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $individuels = $statMatchService->calculerPourParticipant($user);

        $equipes = $user->getType() === TypeUtilisateur::Joueur
            ? $statMatchService->calculerPourEquipesJoueur($user)
            : null;

        return $this->json([
            'individuels' => $individuels,
            'equipes'     => $equipes,
        ]);
    }
}
