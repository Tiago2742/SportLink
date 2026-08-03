<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_avis_notant_game', fields: ['notant', 'game'])]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['avis:read'])]
    private ?Utilisateur $notant = null;

    #[ORM\ManyToOne(inversedBy: 'avisRecus')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['avis:read'])]
    private ?Utilisateur $evalue = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?int $ponctualite = null;

    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?int $fairPlay = null;

    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?int $niveauConforme = null;

    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?\DateTimeImmutable $dateCreation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotant(): ?Utilisateur
    {
        return $this->notant;
    }

    public function setNotant(?Utilisateur $notant): static
    {
        $this->notant = $notant;
        return $this;
    }

    public function getEvalue(): ?Utilisateur
    {
        return $this->evalue;
    }

    public function setEvalue(?Utilisateur $evalue): static
    {
        $this->evalue = $evalue;
        return $this;
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

    public function getPonctualite(): ?int
    {
        return $this->ponctualite;
    }

    public function setPonctualite(int $ponctualite): static
    {
        $this->ponctualite = $ponctualite;
        return $this;
    }

    public function getFairPlay(): ?int
    {
        return $this->fairPlay;
    }

    public function setFairPlay(int $fairPlay): static
    {
        $this->fairPlay = $fairPlay;
        return $this;
    }

    public function getNiveauConforme(): ?int
    {
        return $this->niveauConforme;
    }

    public function setNiveauConforme(int $niveauConforme): static
    {
        $this->niveauConforme = $niveauConforme;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }
}
