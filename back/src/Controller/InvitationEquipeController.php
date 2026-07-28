<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use App\Service\EquipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/invitations-equipes')]
class InvitationEquipeController extends AbstractController
{
    public function __construct(
        private EquipeService $equipeService,
    ) {}

    #[Route('', name: 'api_invitations_equipes_lister', methods: ['GET'])]
    public function lister(): JsonResponse
    {
        /** @var Utilisateur $moi */
        $moi = $this->getUser();
        if ($moi->getType() !== TypeUtilisateur::Joueur) {
            return $this->json(['erreur' => 'Réservé aux comptes joueur.'], 403);
        }

        $invitations = $this->equipeService->listerInvitationsEnAttente($moi);

        return $this->json($invitations, 200, [], [
            'groups' => [
                'equipe_joueur:read',
                'equipe_joueur:invitation',
                'utilisateur:public',
                'equipe:list',
                'sport:read',
                'niveau:read',
            ],
        ]);
    }
}
