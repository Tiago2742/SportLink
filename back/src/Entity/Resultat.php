<?php

namespace App\Entity;

use App\Repository\ResultatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

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

    #[ORM\Column]
    #[Groups(['resultat:read'])]
    private ?int $scoreCamp1 = null;

    #[ORM\Column]
    #[Groups(['resultat:read'])]
    private ?int $scoreCamp2 = null;

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

    public function getScoreCamp1(): ?int
    {
        return $this->scoreCamp1;
    }

    public function setScoreCamp1(int $scoreCamp1): static
    {
        $this->scoreCamp1 = $scoreCamp1;
        return $this;
    }

    public function getScoreCamp2(): ?int
    {
        return $this->scoreCamp2;
    }

    public function setScoreCamp2(int $scoreCamp2): static
    {
        $this->scoreCamp2 = $scoreCamp2;
        return $this;
    }
}
