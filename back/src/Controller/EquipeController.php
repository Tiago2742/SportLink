<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Enum\TypeUtilisateur;
use App\Repository\EquipeRepository;
use App\Repository\NiveauRepository;
use App\Repository\SportRepository;
use App\Service\EquipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/equipes')]
class EquipeController extends AbstractController
{
    public function __construct(
        private EquipeService    $equipeService,
        private EquipeRepository $equipeRepository,
        private SportRepository  $sportRepository,
        private NiveauRepository $niveauRepository,
    ) {}

    private const GROUPES_LIST = ['equipe:list', 'utilisateur:read', 'sport:read', 'niveau:read'];
    private const GROUPES_READ = ['equipe:read', 'utilisateur:read', 'sport:read', 'niveau:read', 'equipe_joueur:read'];

    #[Route('', name: 'api_equipes_lister', methods: ['GET'])]
    public function lister(Request $request): JsonResponse
    {
        $equipes = $this->equipeRepository->trouverAvecFiltres(
            $request->query->getInt('sportId') ?: null,
            $request->query->getInt('niveauId') ?: null,
            $request->query->get('localisation'),
            $request->query->getInt('clubId') ?: null,
        );

        return $this->json($equipes, 200, [], ['groups' => self::GROUPES_LIST]);
    }

    #[Route('', name: 'api_equipes_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $moi = $this->getUser();
        if ($moi->getType() !== TypeUtilisateur::Club) {
            return $this->json(['erreur' => 'Seul un compte club peut créer une équipe.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);
        if (!\is_array($donnees)) {
            return $this->json(['erreur' => 'Corps JSON invalide.'], 400);
        }

        if (empty($donnees['nom']) || empty($donnees['sportId'])) {
            return $this->json(['erreur' => 'Les champs "nom" et "sportId" sont requis.'], 400);
        }

        $sport = $this->sportRepository->find((int) $donnees['sportId']);
        if (!$sport) {
            return $this->json(['erreur' => 'Sport introuvable.'], 404);
        }

        // R4 : le niveau doit appartenir au sport
        $niveau = null;
        if (!empty($donnees['niveauId'])) {
            $niveau = $this->niveauRepository->find((int) $donnees['niveauId']);
            if (!$niveau || $niveau->getSport() !== $sport) {
                return $this->json(['erreur' => 'Niveau invalide pour ce sport (R4).'], 422);
            }
        }

        try {
            $equipe = $this->equipeService->creer(
                trim((string) $donnees['nom']),
                $sport,
                $niveau,
                isset($donnees['localisation']) && $donnees['localisation'] !== ''
                    ? (string) $donnees['localisation']
                    : null,
                isset($donnees['logo']) && $donnees['logo'] !== ''
                    ? (string) $donnees['logo']
                    : null,
                $moi,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($equipe, 201, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_equipes_afficher', methods: ['GET'])]
    public function afficher(Equipe $equipe): JsonResponse
    {
        return $this->json($equipe, 200, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_equipes_modifier', methods: ['PUT'])]
    public function modifier(Request $request, Equipe $equipe): JsonResponse
    {
        if ($equipe->getClub() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        $sport = null;
        if (!empty($donnees['sportId'])) {
            $sport = $this->sportRepository->find((int) $donnees['sportId']);
            if (!$sport) {
                return $this->json(['erreur' => 'Sport introuvable.'], 404);
            }
        }

        $niveau        = null;
        $effacerNiveau = false;
        if (array_key_exists('niveauId', $donnees)) {
            if ($donnees['niveauId'] !== null) {
                $sportRef = $sport ?? $equipe->getSport();
                $niveau   = $this->niveauRepository->find((int) $donnees['niveauId']);
                if (!$niveau || $niveau->getSport() !== $sportRef) {
                    return $this->json(['erreur' => 'Niveau invalide pour ce sport (R4).'], 422);
                }
            } else {
                $effacerNiveau = true;
            }
        }

        $equipe = $this->equipeService->modifier(
            $equipe,
            $donnees['nom'] ?? null,
            $sport,
            $niveau,
            $effacerNiveau,
            $donnees['localisation'] ?? null,
            $donnees['logo'] ?? null,
        );

        return $this->json($equipe, 200, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_equipes_supprimer', methods: ['DELETE'])]
    public function supprimer(Equipe $equipe): JsonResponse
    {
        if ($equipe->getClub() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $this->equipeService->supprimer($equipe);

        return $this->json(null, 204);
    }

    // ---- Membres ------------------------------------------------------------

    #[Route('/{id}/membres', name: 'api_equipes_ajouter_membre', methods: ['POST'])]
    public function ajouterMembre(Request $request, Equipe $equipe): JsonResponse
    {
        if ($equipe->getClub() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['utilisateur_id'])) {
            return $this->json(['erreur' => 'Le champ "utilisateur_id" est requis.'], 400);
        }

        try {
            $membre = $this->equipeService->ajouterMembre(
                $equipe,
                (int) $donnees['utilisateur_id'],
                $donnees['role'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($membre, 201, [], ['groups' => ['equipe_joueur:read', 'utilisateur:read']]);
    }

    #[Route('/{id}/membres/{membreId}', name: 'api_equipes_retirer_membre', methods: ['DELETE'])]
    public function retirerMembre(Equipe $equipe, int $membreId): JsonResponse
    {
        if ($equipe->getClub() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $membre = null;
        foreach ($equipe->getMembres() as $m) {
            if ($m->getId() === $membreId) {
                $membre = $m;
                break;
            }
        }

        if (!$membre) {
            return $this->json(['erreur' => 'Membre introuvable dans cette équipe.'], 404);
        }

        $this->equipeService->retirerMembre($membre);

        return $this->json(null, 204);
    }
}
