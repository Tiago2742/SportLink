<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use App\Service\EquipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/equipes')]
class EquipeController extends AbstractController
{
    public function __construct(
        private EquipeService $equipeService,
        private EquipeRepository $equipeRepository,
    ) {}

    #[Route('', name: 'api_equipes_lister', methods: ['GET'])]
    public function lister(Request $request): JsonResponse
    {
        $equipes = $this->equipeRepository->trouverAvecFiltres(
            $request->query->get('sport'),
            $request->query->get('niveau'),
            $request->query->get('localisation'),
            $request->query->getInt('createurId') ?: null,
        );

        return $this->json($equipes, 200, [], ['groups' => ['equipe:list', 'utilisateur:read']]);
    }

    #[Route('', name: 'api_equipes_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['nom']) || empty($donnees['sport'])) {
            return $this->json(['erreur' => 'Les champs "nom" et "sport" sont requis.'], 400);
        }

        $equipe = $this->equipeService->creer($donnees, $this->getUser());

        return $this->json($equipe, 201, [], ['groups' => ['equipe:read', 'utilisateur:read', 'equipe_joueur:read']]);
    }

    #[Route('/{id}', name: 'api_equipes_afficher', methods: ['GET'])]
    public function afficher(Equipe $equipe): JsonResponse
    {
        return $this->json($equipe, 200, [], ['groups' => ['equipe:read', 'utilisateur:read', 'equipe_joueur:read']]);
    }

    #[Route('/{id}', name: 'api_equipes_modifier', methods: ['PUT'])]
    public function modifier(Request $request, Equipe $equipe): JsonResponse
    {
        if ($equipe->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);
        $equipe = $this->equipeService->modifier($equipe, $donnees);

        return $this->json($equipe, 200, [], ['groups' => ['equipe:read', 'utilisateur:read', 'equipe_joueur:read']]);
    }

    #[Route('/{id}', name: 'api_equipes_supprimer', methods: ['DELETE'])]
    public function supprimer(Equipe $equipe): JsonResponse
    {
        if ($equipe->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $this->equipeService->supprimer($equipe);

        return $this->json(null, 204);
    }

    // --- Membres ---

    #[Route('/{id}/membres', name: 'api_equipes_ajouter_membre', methods: ['POST'])]
    public function ajouterMembre(Request $request, Equipe $equipe): JsonResponse
    {
        if ($equipe->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['utilisateur_id'])) {
            return $this->json(['erreur' => 'Le champ "utilisateur_id" est requis.'], 400);
        }

        try {
            $membre = $this->equipeService->ajouterMembre($equipe, (int) $donnees['utilisateur_id'], $donnees['role'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($membre, 201, [], ['groups' => ['equipe_joueur:read', 'utilisateur:read']]);
    }

    #[Route('/{id}/membres/{membreId}', name: 'api_equipes_retirer_membre', methods: ['DELETE'])]
    public function retirerMembre(Equipe $equipe, int $membreId): JsonResponse
    {
        if ($equipe->getCreateur() !== $this->getUser()) {
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
