<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\DisputerRepository;
use App\Repository\GameRepository;
use App\Repository\MessageRepository;
use App\Repository\ParticipationRepository;
use App\Service\MatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/matchs')]
class MatchController extends AbstractController
{
    public function __construct(
        private MatchService $matchService,
        private GameRepository $gameRepository,
        private ParticipationRepository $participationRepository,
        private DisputerRepository $disputerRepository,
        private MessageRepository $messageRepository,
    ) {}

    #[Route('', name: 'api_matchs_lister', methods: ['GET'])]
    public function lister(Request $request): JsonResponse
    {
        $matchs = $this->gameRepository->trouverAvecFiltres(
            $request->query->get('sport'),
            $request->query->get('lieu'),
            $request->query->get('statut'),
            $request->query->get('niveauRequis'),
            $request->query->getInt('createurId') ?: null,
        );

        return $this->json($matchs, 200, [], ['groups' => ['game:list', 'utilisateur:read']]);
    }

    #[Route('', name: 'api_matchs_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['sport']) || empty($donnees['dateMatch'])) {
            return $this->json(['erreur' => 'Les champs "sport" et "dateMatch" sont requis.'], 400);
        }

        try {
            $match = $this->matchService->creer($donnees, $this->getUser());
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Format de date invalide. Utilisez : YYYY-MM-DD HH:MM'], 400);
        }

        return $this->json($match, 201, [], ['groups' => ['game:read', 'utilisateur:read', 'participation:read']]);
    }

    #[Route('/{id}', name: 'api_matchs_afficher', methods: ['GET'])]
    public function afficher(Game $match): JsonResponse
    {
        return $this->json($match, 200, [], ['groups' => ['game:read', 'utilisateur:read', 'participation:read']]);
    }

    #[Route('/{id}', name: 'api_matchs_modifier', methods: ['PUT'])]
    public function modifier(Request $request, Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        try {
            $match = $this->matchService->modifier($match, $donnees);
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Format de date invalide. Utilisez : YYYY-MM-DD HH:MM'], 400);
        }

        return $this->json($match, 200, [], ['groups' => ['game:read', 'utilisateur:read', 'participation:read']]);
    }

    #[Route('/{id}', name: 'api_matchs_supprimer', methods: ['DELETE'])]
    public function supprimer(Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $this->matchService->supprimer($match);

        return $this->json(null, 204);
    }

    // --- Participations ---

    #[Route('/{id}/participations', name: 'api_matchs_participer', methods: ['POST'])]
    public function participer(Game $match): JsonResponse
    {
        try {
            $participation = $this->matchService->participer($match, $this->getUser());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($participation, 201, [], ['groups' => ['participation:read', 'utilisateur:read']]);
    }

    #[Route('/{id}/participations/{participationId}', name: 'api_matchs_modifier_statut', methods: ['PATCH'])]
    public function modifierStatut(Request $request, Game $match, int $participationId): JsonResponse
    {
        $participation = $this->participationRepository->find($participationId);

        if (!$participation || $participation->getGame() !== $match) {
            return $this->json(['erreur' => 'Participation introuvable.'], 404);
        }

        $utilisateurConnecte = $this->getUser();
        $estCreateur = $match->getCreateur() === $utilisateurConnecte;
        $estParticipant = $participation->getUtilisateur() === $utilisateurConnecte;

        if (!$estCreateur && !$estParticipant) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['statut'])) {
            return $this->json(['erreur' => 'Le champ "statut" est requis.'], 400);
        }

        try {
            $participation = $this->matchService->modifierStatut($participation, $donnees['statut']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($participation, 200, [], ['groups' => ['participation:read', 'utilisateur:read']]);
    }

    #[Route('/{id}/participations/{participationId}', name: 'api_matchs_annuler_participation', methods: ['DELETE'])]
    public function annulerParticipation(Game $match, int $participationId): JsonResponse
    {
        $participation = $this->participationRepository->find($participationId);

        if (!$participation || $participation->getGame() !== $match) {
            return $this->json(['erreur' => 'Participation introuvable.'], 404);
        }

        $utilisateurConnecte = $this->getUser();
        $estCreateur = $match->getCreateur() === $utilisateurConnecte;
        $estParticipant = $participation->getUtilisateur() === $utilisateurConnecte;

        if (!$estCreateur && !$estParticipant) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $this->matchService->annulerParticipation($participation);

        return $this->json(null, 204);
    }

    // --- Équipes disputant ---

    #[Route('/{id}/equipes', name: 'api_matchs_inscrire_equipe', methods: ['POST'])]
    public function inscrireEquipe(Request $request, Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['equipe_id'])) {
            return $this->json(['erreur' => 'Le champ "equipe_id" est requis.'], 400);
        }

        try {
            $disputer = $this->matchService->inscrireEquipe($match, (int) $donnees['equipe_id'], $donnees['role'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($disputer, 201, [], ['groups' => ['disputer:read', 'equipe:list', 'utilisateur:read']]);
    }

    #[Route('/{id}/equipes/{disputeId}', name: 'api_matchs_retirer_equipe', methods: ['DELETE'])]
    public function retirerEquipe(Game $match, int $disputeId): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $disputer = $this->disputerRepository->find($disputeId);

        if (!$disputer || $disputer->getGame() !== $match) {
            return $this->json(['erreur' => 'Inscription introuvable pour ce match.'], 404);
        }

        $this->matchService->retirerEquipe($disputer);

        return $this->json(null, 204);
    }

    // --- Messages ---

    #[Route('/{id}/messages', name: 'api_matchs_lister_messages', methods: ['GET'])]
    public function listerMessages(Game $match): JsonResponse
    {
        if (!$this->matchService->estParticipant($match, $this->getUser())) {
            return $this->json(['erreur' => 'Réservé aux participants confirmés du match.'], 403);
        }

        $messages = $this->messageRepository->findBy(
            ['game' => $match],
            ['dateEnvoi' => 'ASC'],
        );

        return $this->json($messages, 200, [], ['groups' => ['message:read', 'utilisateur:read']]);
    }

    #[Route('/{id}/messages', name: 'api_matchs_envoyer_message', methods: ['POST'])]
    public function envoyerMessage(Request $request, Game $match): JsonResponse
    {
        if (!$this->matchService->estParticipant($match, $this->getUser())) {
            return $this->json(['erreur' => 'Réservé aux participants confirmés du match.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['contenu'])) {
            return $this->json(['erreur' => 'Le champ "contenu" est requis.'], 400);
        }

        $message = $this->matchService->envoyerMessage($match, $this->getUser(), $donnees['contenu']);

        return $this->json($message, 201, [], ['groups' => ['message:read', 'utilisateur:read']]);
    }

    // --- Résultat ---

    #[Route('/{id}/resultat', name: 'api_matchs_afficher_resultat', methods: ['GET'])]
    public function afficherResultat(Game $match): JsonResponse
    {
        $resultat = $match->getResultat();

        if (!$resultat) {
            return $this->json(['erreur' => 'Aucun résultat enregistré pour ce match.'], 404);
        }

        return $this->json($resultat, 200, [], ['groups' => ['resultat:read']]);
    }

    #[Route('/{id}/resultat', name: 'api_matchs_saisir_resultat', methods: ['POST'])]
    public function saisirResultat(Request $request, Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (!isset($donnees['scoreEquipe1'], $donnees['scoreEquipe2'])) {
            return $this->json(['erreur' => 'Les champs "scoreEquipe1" et "scoreEquipe2" sont requis.'], 400);
        }

        try {
            $resultat = $this->matchService->saisirResultat(
                $match,
                (int) $donnees['scoreEquipe1'],
                (int) $donnees['scoreEquipe2'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($resultat, 201, [], ['groups' => ['resultat:read']]);
    }

    #[Route('/{id}/resultat', name: 'api_matchs_modifier_resultat', methods: ['PUT'])]
    public function modifierResultat(Request $request, Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (!isset($donnees['scoreEquipe1'], $donnees['scoreEquipe2'])) {
            return $this->json(['erreur' => 'Les champs "scoreEquipe1" et "scoreEquipe2" sont requis.'], 400);
        }

        try {
            $resultat = $this->matchService->modifierResultat(
                $match,
                (int) $donnees['scoreEquipe1'],
                (int) $donnees['scoreEquipe2'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($resultat, 200, [], ['groups' => ['resultat:read']]);
    }
}
