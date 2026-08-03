<?php

namespace App\Controller;

use App\Entity\Game;
use App\Enum\StatutDemande;
use App\Exception\DemandeEnAttenteException;
use App\Repository\DemandeMatchRepository;
use App\Service\DemandeMatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/matchs')]
class DemandeMatchController extends AbstractController
{
    private const GROUPES_DEMANDE = ['demande:read', 'utilisateur:public', 'equipe:list'];

    public function __construct(
        private DemandeMatchService    $demandeMatchService,
        private DemandeMatchRepository $demandeMatchRepository,
    ) {}

    // -------------------------------------------------------------------------
    // POST — un non-créateur demande à rejoindre le match
    // -------------------------------------------------------------------------

    #[Route('/{id}/demandes', name: 'api_demandes_creer', methods: ['POST'])]
    public function demander(Request $request, Game $match): JsonResponse
    {
        if ($this->getUser() === $match->getCreateur()) {
            return $this->json(['erreur' => 'Le créateur ne peut pas demander à rejoindre son propre match.'], 403);
        }

        $donnees  = json_decode($request->getContent(), true) ?? [];
        $equipeId = isset($donnees['equipeId']) ? (int) $donnees['equipeId'] : null;

        try {
            $demande = $this->demandeMatchService->demander($match, $this->getUser(), $equipeId);
        } catch (DemandeEnAttenteException $e) {
            return $this->json(['erreur' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($demande, 201, [], ['groups' => self::GROUPES_DEMANDE]);
    }

    // -------------------------------------------------------------------------
    // GET — le créateur consulte la liste des demandes (filtre ?statut optionnel)
    // -------------------------------------------------------------------------

    #[Route('/{id}/demandes', name: 'api_demandes_lister', methods: ['GET'])]
    public function lister(Request $request, Game $match): JsonResponse
    {
        if ($this->getUser() !== $match->getCreateur()) {
            return $this->json(['erreur' => 'Accès réservé au créateur du match.'], 403);
        }

        $criteria = ['game' => $match];

        $statutParam = $request->query->get('statut');
        if ($statutParam !== null) {
            try {
                $criteria['statut'] = StatutDemande::from($statutParam);
            } catch (\ValueError) {
                return $this->json(['erreur' => 'Statut invalide. Valeurs : en_attente, acceptee, refusee, annulee.'], 400);
            }
        }

        $demandes = $this->demandeMatchRepository->findBy($criteria, ['dateCreation' => 'DESC']);

        return $this->json($demandes, 200, [], ['groups' => self::GROUPES_DEMANDE]);
    }

    // -------------------------------------------------------------------------
    // GET — n'importe qui consulte SA propre demande sur ce match
    // -------------------------------------------------------------------------

    #[Route('/{id}/ma-demande', name: 'api_demandes_ma_demande', methods: ['GET'])]
    public function maDemande(Game $match): JsonResponse
    {
        $demande = $this->demandeMatchRepository->findExistante($match, $this->getUser());

        if (!$demande) {
            return $this->json(['erreur' => 'Aucune demande trouvée.'], 404);
        }

        return $this->json($demande, 200, [], ['groups' => self::GROUPES_DEMANDE]);
    }

    // -------------------------------------------------------------------------
    // PATCH — le créateur accepte ou refuse une demande
    // -------------------------------------------------------------------------

    #[Route('/{id}/demandes/{demandeId}', name: 'api_demandes_repondre', methods: ['PATCH'])]
    public function repondre(Request $request, Game $match, int $demandeId): JsonResponse
    {
        if ($this->getUser() !== $match->getCreateur()) {
            return $this->json(['erreur' => 'Accès réservé au créateur du match.'], 403);
        }

        $demande = $this->demandeMatchRepository->find($demandeId);
        if (!$demande || $demande->getGame() !== $match) {
            return $this->json(['erreur' => 'Demande introuvable pour ce match.'], 404);
        }

        $donnees    = json_decode($request->getContent(), true) ?? [];
        $statutStr  = $donnees['statut'] ?? null;

        try {
            if ($statutStr === StatutDemande::Acceptee->value) {
                $this->demandeMatchService->accepter($demande);
            } elseif ($statutStr === StatutDemande::Refusee->value) {
                $this->demandeMatchService->refuser($demande);
            } else {
                return $this->json(['erreur' => 'Statut invalide. Valeurs acceptées : acceptee, refusee.'], 400);
            }
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json($demande, 200, [], ['groups' => self::GROUPES_DEMANDE]);
    }

    // -------------------------------------------------------------------------
    // DELETE — le demandeur annule sa propre demande en attente
    // -------------------------------------------------------------------------

    #[Route('/{id}/demandes/{demandeId}', name: 'api_demandes_annuler', methods: ['DELETE'])]
    public function annuler(Game $match, int $demandeId): JsonResponse
    {
        $demande = $this->demandeMatchRepository->find($demandeId);
        if (!$demande || $demande->getGame() !== $match) {
            return $this->json(['erreur' => 'Demande introuvable pour ce match.'], 404);
        }

        if ($this->getUser() !== $demande->getDemandeur()) {
            return $this->json(['erreur' => 'Seul le demandeur peut annuler sa propre demande.'], 403);
        }

        try {
            $this->demandeMatchService->annuler($demande);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        }

        return $this->json(null, 204);
    }
}
