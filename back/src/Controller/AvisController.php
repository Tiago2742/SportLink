<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Service\AvisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AvisController extends AbstractController
{
    public function __construct(
        private AvisService    $avisService,
        private AvisRepository $avisRepository,
    ) {}

    /** POST /api/matchs/{id}/avis — déposer une évaluation après un match terminé. */
    #[Route('/api/matchs/{id}/avis', name: 'api_avis_deposer', methods: ['POST'])]
    public function deposerAvis(Request $request, Game $match): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);

        if (!isset($donnees['ponctualite'], $donnees['fairPlay'], $donnees['niveauConforme'])) {
            return $this->json(['erreur' => 'Les champs "ponctualite", "fairPlay" et "niveauConforme" sont requis.'], 400);
        }

        try {
            $avis = $this->avisService->deposerAvis(
                $match,
                $this->getUser(),
                (int) $donnees['ponctualite'],
                (int) $donnees['fairPlay'],
                (int) $donnees['niveauConforme'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($avis, 201, [], ['groups' => ['avis:read', 'utilisateur:public']]);
    }

    /** GET /api/profils/{id} — profil public d'un utilisateur avec sa réputation. */
    #[Route('/api/profils/{id}', name: 'api_profils_afficher', methods: ['GET'])]
    public function afficherProfil(Utilisateur $profil): JsonResponse
    {
        $profil->setReputation($this->avisRepository->calculerMoyennesRecues($profil));

        return $this->json($profil, 200, [], [
            'groups' => ['utilisateur:public', 'utilisateur:detail', 'utilisateur_niveau:read', 'sport:read', 'niveau:read'],
        ]);
    }
}
