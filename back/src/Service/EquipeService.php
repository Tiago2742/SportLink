<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class EquipeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    public function creer(array $donnees, Utilisateur $createur): Equipe
    {
        $equipe = new Equipe();
        $equipe->setNom($donnees['nom']);
        $equipe->setSport($donnees['sport']);
        $equipe->setNiveau($donnees['niveau'] ?? null);
        $equipe->setLocalisation($donnees['localisation'] ?? null);
        $equipe->setLogo($donnees['logo'] ?? null);
        $equipe->setCreateur($createur);

        $this->em->persist($equipe);
        $this->em->flush();

        return $equipe;
    }

    public function modifier(Equipe $equipe, array $donnees): Equipe
    {
        if (isset($donnees['nom']))                         $equipe->setNom($donnees['nom']);
        if (isset($donnees['sport']))                       $equipe->setSport($donnees['sport']);
        if (array_key_exists('niveau', $donnees))           $equipe->setNiveau($donnees['niveau']);
        if (array_key_exists('localisation', $donnees))     $equipe->setLocalisation($donnees['localisation']);
        if (array_key_exists('logo', $donnees))             $equipe->setLogo($donnees['logo']);

        $this->em->flush();

        return $equipe;
    }

    public function ajouterMembre(Equipe $equipe, int $utilisateurId, ?string $role): EquipeJoueur
    {
        $utilisateur = $this->utilisateurRepository->find($utilisateurId);
        if (!$utilisateur) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        foreach ($equipe->getMembres() as $membre) {
            if ($membre->getUtilisateur() === $utilisateur) {
                throw new \InvalidArgumentException('Cet utilisateur est déjà membre de l\'équipe.');
            }
        }

        $membreEquipe = new EquipeJoueur();
        $membreEquipe->setUtilisateur($utilisateur);
        $membreEquipe->setEquipe($equipe);
        $membreEquipe->setRole($role);

        $this->em->persist($membreEquipe);
        $this->em->flush();

        return $membreEquipe;
    }

    public function retirerMembre(EquipeJoueur $membre): void
    {
        $this->em->remove($membre);
        $this->em->flush();
    }

    public function supprimer(Equipe $equipe): void
    {
        $this->em->remove($equipe);
        $this->em->flush();
    }
}
