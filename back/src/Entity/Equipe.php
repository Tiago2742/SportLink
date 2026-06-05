<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $nom = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?Sport $sport = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?Niveau $niveau = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $logo = null;

    #[ORM\ManyToOne(inversedBy: 'equipesGerees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?Utilisateur $club = null;

    /** @var Collection<int, EquipeJoueur> */
    #[ORM\OneToMany(mappedBy: 'equipe', targetEntity: EquipeJoueur::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['equipe:read'])]
    private Collection $membres;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSport(): ?Sport
    {
        return $this->sport;
    }

    public function setSport(?Sport $sport): static
    {
        $this->sport = $sport;
        return $this;
    }

    public function getNiveau(): ?Niveau
    {
        return $this->niveau;
    }

    public function setNiveau(?Niveau $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getClub(): ?Utilisateur
    {
        return $this->club;
    }

    public function setClub(?Utilisateur $club): static
    {
        $this->club = $club;
        return $this;
    }

    /** @return Collection<int, EquipeJoueur> */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(EquipeJoueur $membre): static
    {
        if (!$this->membres->contains($membre)) {
            $this->membres->add($membre);
            $membre->setEquipe($this);
        }
        return $this;
    }

    public function removeMembre(EquipeJoueur $membre): static
    {
        if ($this->membres->removeElement($membre)) {
            if ($membre->getEquipe() === $this) {
                $membre->setEquipe(null);
            }
        }
        return $this;
    }
}
