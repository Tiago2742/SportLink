<?php

namespace App\Entity;

use App\Enum\OrigineMembreEquipe;
use App\Enum\RoleEquipe;
use App\Enum\StatutMembreEquipe;
use App\Repository\EquipeJoueurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EquipeJoueurRepository::class)]
class EquipeJoueur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['equipe_joueur:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'equipesJoueur')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['equipe_joueur:read'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'membres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipe $equipe = null;

    #[ORM\Column(enumType: RoleEquipe::class)]
    #[Groups(['equipe_joueur:read'])]
    private ?RoleEquipe $role = null;

    #[ORM\Column(enumType: StatutMembreEquipe::class)]
    #[Groups(['equipe_joueur:read'])]
    private ?StatutMembreEquipe $statut = null;

    #[ORM\Column(enumType: OrigineMembreEquipe::class)]
    #[Groups(['equipe_joueur:read'])]
    private ?OrigineMembreEquipe $origine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getEquipe(): ?Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipe $equipe): static
    {
        $this->equipe = $equipe;
        return $this;
    }

    public function getRole(): ?RoleEquipe
    {
        return $this->role;
    }

    public function setRole(RoleEquipe $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getStatut(): ?StatutMembreEquipe
    {
        return $this->statut;
    }

    public function setStatut(StatutMembreEquipe $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getOrigine(): ?OrigineMembreEquipe
    {
        return $this->origine;
    }

    public function setOrigine(OrigineMembreEquipe $origine): static
    {
        $this->origine = $origine;
        return $this;
    }
}
