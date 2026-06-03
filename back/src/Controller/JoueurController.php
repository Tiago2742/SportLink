<?php

namespace App\Controller;

use App\Enum\TypeUtilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/joueurs')]
class JoueurController extends AbstractController
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    #[Route('/recherche', name: 'api_joueurs_recherche', methods: ['GET'])]
    public function rechercher(Request $request): JsonResponse
    {
        if ($this->getUser()->getType() !== TypeUtilisateur::Club) {
            return $this->json(['erreur' => 'Réservé aux comptes club.'], 403);
        }

        $q = $request->query->get('q', '');
        if (strlen(trim((string) $q)) < 2) {
            return $this->json(['erreur' => 'Saisissez au moins 2 caractères pour rechercher.'], 400);
        }

        $joueurs = $this->utilisateurRepository->rechercherJoueurs((string) $q);

        return $this->json($joueurs, 200, [], ['groups' => ['utilisateur:read']]);
    }
}
