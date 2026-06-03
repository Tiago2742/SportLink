<?php

namespace App\Service;

use App\Entity\Disputer;
use App\Entity\Game;
use App\Entity\Message;
use App\Entity\Participation;
use App\Entity\Resultat;
use App\Entity\Utilisateur;
use App\Repository\EquipeRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatchService
{
    public const STATUTS_VALIDES = ['invité', 'confirmé', 'refusé'];

    public function __construct(
        private EntityManagerInterface $em,
        private ParticipationRepository $participationRepository,
        private EquipeRepository $equipeRepository,
    ) {}

    // --- Match ---

    public function creer(array $donnees, Utilisateur $createur): Game
    {
        $match = new Game();
        $match->setSport($donnees['sport']);
        $match->setDateMatch(new \DateTime($donnees['dateMatch']));
        $match->setLieu($donnees['lieu'] ?? null);
        $match->setNiveauRequis($donnees['niveauRequis'] ?? null);
        $match->setStatut($donnees['statut'] ?? 'ouvert');
        $match->setCreateur($createur);

        $this->em->persist($match);
        $this->em->flush();

        return $match;
    }

    public function modifier(Game $match, array $donnees): Game
    {
        if (isset($donnees['sport']))                    $match->setSport($donnees['sport']);
        if (isset($donnees['dateMatch']))                $match->setDateMatch(new \DateTime($donnees['dateMatch']));
        if (array_key_exists('lieu', $donnees))          $match->setLieu($donnees['lieu']);
        if (array_key_exists('niveauRequis', $donnees))  $match->setNiveauRequis($donnees['niveauRequis']);
        if (array_key_exists('statut', $donnees))        $match->setStatut($donnees['statut']);

        $this->em->flush();

        return $match;
    }

    public function supprimer(Game $match): void
    {
        $this->em->remove($match);
        $this->em->flush();
    }

    // --- Participations ---

    public function participer(Game $match, Utilisateur $utilisateur): Participation
    {
        $participationExistante = $this->participationRepository->findOneBy([
            'game'        => $match,
            'utilisateur' => $utilisateur,
        ]);

        if ($participationExistante) {
            throw new \InvalidArgumentException('Vous participez déjà à ce match.');
        }

        $participation = new Participation();
        $participation->setGame($match);
        $participation->setUtilisateur($utilisateur);
        $participation->setStatut('invité');

        $this->em->persist($participation);
        $this->em->flush();

        return $participation;
    }

    public function modifierStatut(Participation $participation, string $statut): Participation
    {
        if (!in_array($statut, self::STATUTS_VALIDES, true)) {
            throw new \InvalidArgumentException(
                'Statut invalide. Valeurs acceptées : ' . implode(', ', self::STATUTS_VALIDES) . '.'
            );
        }

        $participation->setStatut($statut);
        $this->em->flush();

        return $participation;
    }

    public function annulerParticipation(Participation $participation): void
    {
        $this->em->remove($participation);
        $this->em->flush();
    }

    // --- Équipes disputant ---

    public function inscrireEquipe(Game $match, int $equipeId, ?string $role): Disputer
    {
        $equipe = $this->equipeRepository->find($equipeId);
        if (!$equipe) {
            throw new \InvalidArgumentException('Équipe introuvable.');
        }

        foreach ($match->getEquipesDisputant() as $dispute) {
            if ($dispute->getEquipe() === $equipe) {
                throw new \InvalidArgumentException('Cette équipe est déjà inscrite à ce match.');
            }
        }

        if (count($match->getEquipesDisputant()) >= 2) {
            throw new \InvalidArgumentException('Ce match a déjà deux équipes inscrites.');
        }

        $disputer = new Disputer();
        $disputer->setGame($match);
        $disputer->setEquipe($equipe);
        $disputer->setRole($role);

        $this->em->persist($disputer);
        $this->em->flush();

        return $disputer;
    }

    public function retirerEquipe(Disputer $disputer): void
    {
        $this->em->remove($disputer);
        $this->em->flush();
    }

    // --- Messages ---

    public function envoyerMessage(Game $match, Utilisateur $expediteur, string $contenu): Message
    {
        $message = new Message();
        $message->setGame($match);
        $message->setExpediteur($expediteur);
        $message->setContenu($contenu);
        $message->setDateEnvoi(new \DateTime());

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    // --- Résultat ---

    public function saisirResultat(Game $match, int $scoreEquipe1, int $scoreEquipe2): Resultat
    {
        if ($match->getResultat()) {
            throw new \InvalidArgumentException('Ce match a déjà un résultat. Utilisez PUT pour le modifier.');
        }

        $resultat = new Resultat();
        $resultat->setGame($match);
        $resultat->setScoreEquipe1($scoreEquipe1);
        $resultat->setScoreEquipe2($scoreEquipe2);

        $this->em->persist($resultat);
        $this->em->flush();

        return $resultat;
    }

    public function modifierResultat(Game $match, int $scoreEquipe1, int $scoreEquipe2): Resultat
    {
        $resultat = $match->getResultat();
        if (!$resultat) {
            throw new \InvalidArgumentException('Ce match n\'a pas encore de résultat. Utilisez POST pour le créer.');
        }

        $resultat->setScoreEquipe1($scoreEquipe1);
        $resultat->setScoreEquipe2($scoreEquipe2);

        $this->em->flush();

        return $resultat;
    }

    public function estParticipant(Game $match, Utilisateur $utilisateur): bool
    {
        if ($match->getCreateur() === $utilisateur) {
            return true;
        }

        $participation = $this->participationRepository->findOneBy([
            'game'        => $match,
            'utilisateur' => $utilisateur,
            'statut'      => 'confirmé',
        ]);

        return $participation !== null;
    }
}
