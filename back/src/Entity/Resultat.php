<?php

namespace App\Entity;

use App\Repository\ResultatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ResultatRepository::class)]
class Resultat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resultat:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'resultat', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resultat:read'])]
    private ?int $scoreEquipe1 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resultat:read'])]
    private ?int $scoreEquipe2 = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getScoreEquipe1(): ?int
    {
        return $this->scoreEquipe1;
    }

    public function setScoreEquipe1(?int $scoreEquipe1): static
    {
        $this->scoreEquipe1 = $scoreEquipe1;

        return $this;
    }

    public function getScoreEquipe2(): ?int
    {
        return $this->scoreEquipe2;
    }

    public function setScoreEquipe2(?int $scoreEquipe2): static
    {
        $this->scoreEquipe2 = $scoreEquipe2;

        return $this;
    }
}
