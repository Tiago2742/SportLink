<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

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

    #[ORM\Column(length: 255)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $sport = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $niveau = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?string $logo = null;

    #[ORM\ManyToOne(inversedBy: 'equipesCreees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['equipe:list', 'equipe:read'])]
    private ?utilisateur $createur = null;

    /**
     * @var Collection<int, EquipeJoueur>
     */
    #[ORM\OneToMany(targetEntity: EquipeJoueur::class, mappedBy: 'equipe', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['equipe:read'])]
    private Collection $membres;

    /**
     * @var Collection<int, Disputer>
     */
    #[ORM\OneToMany(targetEntity: Disputer::class, mappedBy: 'equipe', orphanRemoval: true)]
    private Collection $matchsDisputes;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->matchsDisputes = new ArrayCollection();
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

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): static
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

    public function getCreateur(): ?utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?utilisateur $createur): static
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * @return Collection<int, EquipeJoueur>
     */
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
            // set the owning side to null (unless already changed)
            if ($membre->getEquipe() === $this) {
                $membre->setEquipe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Disputer>
     */
    public function getMatchsDisputes(): Collection
    {
        return $this->matchsDisputes;
    }

    public function addMatchsDispute(Disputer $matchsDispute): static
    {
        if (!$this->matchsDisputes->contains($matchsDispute)) {
            $this->matchsDisputes->add($matchsDispute);
            $matchsDispute->setEquipe($this);
        }

        return $this;
    }

    public function removeMatchsDispute(Disputer $matchsDispute): static
    {
        if ($this->matchsDisputes->removeElement($matchsDispute)) {
            // set the owning side to null (unless already changed)
            if ($matchsDispute->getEquipe() === $this) {
                $matchsDispute->setEquipe(null);
            }
        }

        return $this;
    }
}
