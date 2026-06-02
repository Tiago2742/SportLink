<?php

namespace App\Entity;

use App\Repository\DisputerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DisputerRepository::class)]
class Disputer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['disputer:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'equipesDisputant')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'matchsDisputes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['disputer:read'])]
    private ?Equipe $equipe = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['disputer:read'])]
    private ?string $role = null;

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

    public function getEquipe(): ?Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipe $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }
}
