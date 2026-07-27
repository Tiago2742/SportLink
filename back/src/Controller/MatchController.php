<?php

namespace App\Controller;

use App\Entity\Game;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Repository\EquipeRepository;
use App\Repository\GameRepository;
use App\Repository\MatchCampRepository;
use App\Repository\MessageRepository;
use App\Repository\NiveauRepository;
use App\Repository\SportRepository;
use App\Repository\UtilisateurNiveauRepository;
use App\Service\MatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/matchs')]
class MatchController extends AbstractController
{
    public function __construct(
        private MatchService                  $matchService,
        private EquipeRepository              $equipeRepository,
        private GameRepository                $gameRepository,
        private MatchCampRepository           $matchCampRepository,
        private MessageRepository             $messageRepository,
        private SportRepository               $sportRepository,
        private NiveauRepository              $niveauRepository,
        private UtilisateurNiveauRepository   $utilisateurNiveauRepository,
    ) {}

    // ---- Groupes de sérialisation réutilisés --------------------------------

    private const GROUPES_LIST = [
        'game:list', 'utilisateur:read', 'sport:read', 'niveau:read',
        'match_camp:read', 'equipe:list',
    ];
    private const GROUPES_READ = ['game:read', 'utilisateur:read', 'sport:read', 'niveau:read',
                                  'match_camp:read', 'equipe:list', 'resultat:read'];

    // ---- CRUD match ---------------------------------------------------------

    #[Route('', name: 'api_matchs_lister', methods: ['GET'])]
    public function lister(Request $request): JsonResponse
    {
        $statut              = $request->query->get('statut');
        $disponibleSeulement = $statut === 'disponible';
        $mesMatchs           = $request->query->getBoolean('mesMatchs');
        $sportIds            = $this->utilisateurNiveauRepository->findSportIdsByUtilisateur($this->getUser()->getId());

        if ($mesMatchs) {
            $matchs = $this->gameRepository->trouverPourParticipant(
                $this->getUser()->getId(),
                $disponibleSeulement ? null : ($statut ?: null),
            );
        } else {
            $matchs = $this->gameRepository->trouverAvecFiltres(
                $request->query->getInt('sportId') ?: null,
                $request->query->get('lieu'),
                $disponibleSeulement ? null : $statut,
                $request->query->getInt('niveauId') ?: null,
                $request->query->getInt('createurId') ?: null,
                $disponibleSeulement,
                $sportIds,
            );
        }

        $this->matchService->cloturerMatchsExpires($matchs);

        return $this->json($matchs, 200, [], ['groups' => self::GROUPES_LIST]);
    }

    #[Route('', name: 'api_matchs_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['sportId']) || empty($donnees['dateMatch'])) {
            return $this->json(['erreur' => 'Les champs "sportId" et "dateMatch" sont requis.'], 400);
        }

        $sport = $this->sportRepository->find((int) $donnees['sportId']);
        if (!$sport) {
            return $this->json(['erreur' => 'Sport introuvable.'], 404);
        }

        // F3a/F3b — cohérence type sport / type utilisateur
        if ($sport->getType() === TypeSport::Individuel && $this->getUser()->getType() !== TypeUtilisateur::Joueur) {
            return $this->json(['erreur' => 'Un match individuel ne peut être créé que par un compte joueur.'], 403);
        }
        if ($sport->getType() === TypeSport::Collectif && $this->getUser()->getType() !== TypeUtilisateur::Club) {
            return $this->json(['erreur' => 'Un match collectif ne peut être créé que par un compte club.'], 403);
        }

        $sportIds = $this->utilisateurNiveauRepository->findSportIdsByUtilisateur($this->getUser()->getId());
        if (!in_array($sport->getId(), $sportIds, true)) {
            return $this->json(['erreur' => "Vous n'avez pas déclaré ce sport dans votre profil."], 422);
        }

        $niveauRequis = null;
        if (!empty($donnees['niveauRequisId'])) {
            $niveauRequis = $this->niveauRepository->find((int) $donnees['niveauRequisId']);
            if (!$niveauRequis || $niveauRequis->getSport() !== $sport) {
                return $this->json(['erreur' => 'Niveau invalide ou non rattaché à ce sport.'], 422);
            }
        }

        $equipeId = !empty($donnees['equipeId']) ? (int) $donnees['equipeId'] : null;

        try {
            $match = $this->matchService->creer(
                $sport,
                $niveauRequis,
                $donnees['dateMatch'],
                $donnees['lieu'] ?? null,
                $this->getUser(),
                $donnees['description'] ?? null,
                $equipeId,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        } catch (\Exception) {
            return $this->json(['erreur' => 'Format de date invalide. Utilisez : YYYY-MM-DD HH:MM'], 400);
        }

        return $this->json($match, 201, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_matchs_afficher', methods: ['GET'])]
    public function afficher(Game $match): JsonResponse
    {
        $this->matchService->cloturerMatchsExpires([$match]);

        return $this->json($match, 200, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_matchs_modifier', methods: ['PUT'])]
    public function modifier(Request $request, Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
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

        $niveauRequis   = null;
        $effacerNiveau  = false;
        if (array_key_exists('niveauRequisId', $donnees)) {
            if ($donnees['niveauRequisId'] !== null) {
                $niveauRequis = $this->niveauRepository->find((int) $donnees['niveauRequisId']);
                if (!$niveauRequis) {
                    return $this->json(['erreur' => 'Niveau introuvable.'], 404);
                }
                // C2/R4 — le niveau doit appartenir au sport du match (ou au nouveau sport si changé)
                $sportRef = $sport ?? $match->getSport();
                if ($niveauRequis->getSport() !== $sportRef) {
                    return $this->json(['erreur' => 'Niveau invalide : il n\'appartient pas au sport de ce match (R4).'], 422);
                }
            } else {
                $effacerNiveau = true;
            }
        }

        $description        = null;
        $effacerDescription = false;
        if (array_key_exists('description', $donnees)) {
            $description        = $donnees['description'] !== null ? (string) $donnees['description'] : null;
            $effacerDescription = $donnees['description'] === null;
        }

        try {
            $match = $this->matchService->modifier(
                $match,
                $sport,
                $niveauRequis,
                $effacerNiveau,
                $donnees['dateMatch'] ?? null,
                $donnees['lieu'] ?? null,
                $description,
                $effacerDescription,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        } catch (\Exception) {
            return $this->json(['erreur' => 'Format de date invalide. Utilisez : YYYY-MM-DD HH:MM'], 400);
        }

        return $this->json($match, 200, [], ['groups' => self::GROUPES_READ]);
    }

    #[Route('/{id}', name: 'api_matchs_supprimer', methods: ['DELETE'])]
    public function supprimer(Game $match): JsonResponse
    {
        if ($match->getCreateur() !== $this->getUser()) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        try {
            $this->matchService->supprimer($match);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json(null, 204);
    }

    // ---- Camps (R5) ---------------------------------------------------------

    #[Route('/{id}/camps', name: 'api_matchs_ajouter_camp', methods: ['POST'])]
    public function ajouterCamp(Request $request, Game $match): JsonResponse
    {
        // A1 — Sport déclaré par le rejoignant
        $sportIds = $this->utilisateurNiveauRepository->findSportIdsByUtilisateur($this->getUser()->getId());
        if (!in_array($match->getSport()->getId(), $sportIds, true)) {
            return $this->json(['erreur' => 'Vous n\'avez pas déclaré ce sport dans votre profil.'], 422);
        }

        $donnees  = json_decode($request->getContent(), true);
        $equipeId = isset($donnees['equipeId']) ? (int) $donnees['equipeId'] : null;
        $joueurId = isset($donnees['joueurId']) ? (int) $donnees['joueurId'] : null;

        $moi = $this->getUser();
        // Invité = réservé à une invitation lancée par un tiers (autre joueur / autre club)
        $confirmerInscription = true;
        if ($joueurId !== null && (int) $joueurId !== $moi->getId()) {
            $confirmerInscription = false;
        }

        try {
            $camp = $this->matchService->creerCamp($match, $equipeId, $joueurId, $moi, $confirmerInscription);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($camp, 201, [], [
            'groups' => ['match_camp:read', 'equipe:list', 'utilisateur:read', 'sport:read', 'niveau:read'],
        ]);
    }

    #[Route('/{id}/camps/{campId}', name: 'api_matchs_repondre_camp', methods: ['PATCH'])]
    public function repondreCamp(Request $request, Game $match, int $campId): JsonResponse
    {
        $camp = $this->matchCampRepository->find($campId);

        if (!$camp || $camp->getGame() !== $match) {
            return $this->json(['erreur' => 'Camp introuvable pour ce match.'], 404);
        }

        // Autorisation : seul le concerné peut répondre
        $moi = $this->getUser();
        $peutRepondre = ($camp->getJoueur() === $moi)
            || ($camp->getEquipe()?->getClub() === $moi);

        if (!$peutRepondre) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['statut'])) {
            return $this->json(['erreur' => 'Le champ "statut" est requis.'], 400);
        }

        try {
            $nouveauStatut = StatutMatchCamp::from($donnees['statut']);
        } catch (\ValueError) {
            return $this->json(['erreur' => 'Statut invalide. Valeurs acceptées : confirme, refuse.'], 400);
        }

        try {
            $camp = $this->matchService->repondreInvitation($camp, $nouveauStatut);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($camp, 200, [], [
            'groups' => ['match_camp:read', 'equipe:list', 'utilisateur:read', 'sport:read', 'niveau:read'],
        ]);
    }

    #[Route('/{id}/camps/{campId}', name: 'api_matchs_supprimer_camp', methods: ['DELETE'])]
    public function supprimerCamp(Game $match, int $campId): JsonResponse
    {
        $camp = $this->matchCampRepository->find($campId);

        if (!$camp || $camp->getGame() !== $match) {
            return $this->json(['erreur' => 'Inscription introuvable pour ce match.'], 404);
        }

        $moi = $this->getUser();
        $estOrganisateur = $match->getCreateur() === $moi;

        if (!$estOrganisateur && !$this->matchService->peutQuitterCamp($camp, $moi)) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        try {
            $this->matchService->supprimerCamp($camp);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json(null, 204);
    }

    // ---- Messages -----------------------------------------------------------

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

        // A7 — Messages interdits sur un match annulé
        if ($match->getStatut() === StatutGame::Annule) {
            return $this->json(['erreur' => 'Impossible d\'envoyer un message sur un match annulé.'], 422);
        }

        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['contenu'])) {
            return $this->json(['erreur' => 'Le champ "contenu" est requis.'], 400);
        }

        $message = $this->matchService->envoyerMessage($match, $this->getUser(), $donnees['contenu']);

        return $this->json($message, 201, [], ['groups' => ['message:read', 'utilisateur:read']]);
    }

    // ---- Résultat -----------------------------------------------------------

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
        if (!$this->matchService->peutSaisirResultat($match, $this->getUser())) {
            return $this->json(['erreur' => 'Accès refusé.'], 403);
        }

        $donnees = json_decode($request->getContent(), true);

        if (!isset($donnees['scoreCamp1'], $donnees['scoreCamp2'])) {
            return $this->json(['erreur' => 'Les champs "scoreCamp1" et "scoreCamp2" sont requis.'], 400);
        }

        try {
            $resultat = $this->matchService->saisirResultat(
                $match,
                (int) $donnees['scoreCamp1'],
                (int) $donnees['scoreCamp2'],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($resultat, 201, [], ['groups' => ['resultat:read']]);
    }

}
