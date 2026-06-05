<?php

namespace App\Entity;

use App\Enum\RoleMatchCamp;
use App\Enum\StatutMatchCamp;
use App\Repository\MatchCampRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MatchCampRepository::class)]
#[ORM\Table(name: 'match_camp')]
class MatchCamp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['match_camp:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'camps')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(enumType: RoleMatchCamp::class)]
    #[Groups(['match_camp:read'])]
    private ?RoleMatchCamp $role = null;

    #[ORM\Column(enumType: StatutMatchCamp::class)]
    #[Groups(['match_camp:read'])]
    private ?StatutMatchCamp $statut = null;

    // Rempli si sport collectif, null sinon (R5)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['match_camp:read'])]
    private ?Equipe $equipe = null;

    // Rempli si sport individuel, null sinon (R5)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['match_camp:read'])]
    private ?Utilisateur $joueur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getRole(): ?RoleMatchCamp
    {
        return $this->role;
    }

    public function setRole(RoleMatchCamp $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getStatut(): ?StatutMatchCamp
    {
        return $this->statut;
    }

    public function setStatut(StatutMatchCamp $statut): static
    {
        $this->statut = $statut;
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

    public function getJoueur(): ?Utilisateur
    {
        return $this->joueur;
    }

    public function setJoueur(?Utilisateur $joueur): static
    {
        $this->joueur = $joueur;
        return $this;
    }
}
