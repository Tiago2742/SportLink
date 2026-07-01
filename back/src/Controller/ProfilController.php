<?php

namespace App\Controller;

use App\Entity\UtilisateurNiveau;
use App\Entity\Utilisateur;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Repository\NiveauRepository;
use App\Repository\SportRepository;
use App\Repository\UtilisateurNiveauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profil')]
class ProfilController extends AbstractController
{
    private const GROUPS = ['utilisateur:read', 'utilisateur:detail', 'utilisateur_niveau:read', 'sport:read', 'niveau:read'];

    #[Route('', methods: ['GET'])]
    public function profil(): JsonResponse
    {
        return $this->json($this->getUser(), 200, [], ['groups' => self::GROUPS]);
    }

    // ── LOGO (clubs uniquement) ───────────────────────────────────────────────

    #[Route('/logo', methods: ['PATCH'])]
    public function mettreAJourLogo(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if ($user->getType() !== TypeUtilisateur::Club) {
            return $this->json(['erreur' => 'Réservé aux clubs.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $url  = trim($data['logo'] ?? '');

        // URL vide = suppression du logo
        $user->setLogo($url !== '' ? $url : null);
        $em->flush();

        return $this->json($user, 200, [], ['groups' => self::GROUPS]);
    }

    // ── SPORTS / NIVEAUX ─────────────────────────────────────────────────────

    /**
     * Ajoute ou met à jour un UtilisateurNiveau (idempotent par sport).
     *
     * Validations :
     *   R4 — le Niveau doit appartenir au Sport envoyé.
     *   Club — seuls les sports collectifs sont autorisés.
     */
    #[Route('/sports', methods: ['POST'])]
    public function ajouterSport(
        Request $request,
        EntityManagerInterface $em,
        SportRepository $sportRepo,
        NiveauRepository $niveauRepo,
        UtilisateurNiveauRepository $unRepo,
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $sport  = $sportRepo->find($data['sportId'] ?? 0);
        $niveau = $niveauRepo->find($data['niveauId'] ?? 0);

        if (!$sport || !$niveau) {
            return $this->json(['erreur' => 'Sport ou niveau introuvable.'], 404);
        }

        // R4 — cohérence niveau/sport
        if ($niveau->getSport()?->getId() !== $sport->getId()) {
            return $this->json(['erreur' => 'Ce niveau n\'appartient pas à ce sport.'], 422);
        }

        // Clubs : collectif uniquement
        if ($user->getType() === TypeUtilisateur::Club && $sport->getType() !== TypeSport::Collectif) {
            return $this->json(['erreur' => 'Un club ne peut déclarer que des sports collectifs.'], 422);
        }

        // Idempotent : cherche un UtilisateurNiveau existant pour ce sport
        $un = $unRepo->findOneBy(['utilisateur' => $user, 'sport' => $sport]);

        if ($un === null) {
            $un = new UtilisateurNiveau();
            $un->setSport($sport);
            $user->addNiveau($un);
        }

        $un->setNiveau($niveau);
        $em->flush();

        return $this->json($user, 200, [], ['groups' => self::GROUPS]);
    }

    /**
     * Change le niveau d'un sport déjà déclaré.
     *
     * PATCH /api/profil/sports/{sportId}   body: { "niveauId": 3 }
     */
    #[Route('/sports/{sportId}', methods: ['PATCH'])]
    public function modifierNiveau(
        int $sportId,
        Request $request,
        EntityManagerInterface $em,
        NiveauRepository $niveauRepo,
        UtilisateurNiveauRepository $unRepo,
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user   = $this->getUser();
        $data   = json_decode($request->getContent(), true);
        $niveau = $niveauRepo->find($data['niveauId'] ?? 0);

        if (!$niveau) {
            return $this->json(['erreur' => 'Niveau introuvable.'], 404);
        }

        $un = $unRepo->findOneBy(['utilisateur' => $user, 'sport' => $sportId]);

        if (!$un) {
            return $this->json(['erreur' => 'Sport non déclaré pour cet utilisateur.'], 404);
        }

        // R4 — cohérence niveau/sport
        if ($niveau->getSport()?->getId() !== $un->getSport()?->getId()) {
            return $this->json(['erreur' => 'Ce niveau n\'appartient pas à ce sport.'], 422);
        }

        $un->setNiveau($niveau);
        $em->flush();

        return $this->json($user, 200, [], ['groups' => self::GROUPS]);
    }
}
