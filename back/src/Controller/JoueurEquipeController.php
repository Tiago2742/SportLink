<?php

namespace App\Controller;

use App\Enum\TypeUtilisateur;
use App\Service\EquipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/joueur')]
class JoueurEquipeController extends AbstractController
{
    private const GROUPES_ESPACE = [
        'equipe_joueur:read',
        'equipe_joueur:invitation',
        'utilisateur:embed',
        'equipe:list',
        'sport:read',
        'niveau:read',
    ];

    public function __construct(
        private EquipeService $equipeService,
    ) {}

    #[Route('/espace-equipes', name: 'api_joueur_espace_equipes', methods: ['GET'])]
    public function espaceEquipes(): JsonResponse
    {
        $moi = $this->getUser();
        if ($moi->getType() !== TypeUtilisateur::Joueur) {
            return $this->json(['erreur' => 'Réservé aux comptes joueur.'], 403);
        }

        $espace = $this->equipeService->listerEspaceJoueur($moi);

        return $this->json($espace, 200, [], ['groups' => self::GROUPES_ESPACE]);
    }
}
