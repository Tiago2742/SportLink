<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:list', 'game:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $sport = null;

    #[ORM\Column]
    #[Groups(['game:list', 'game:read'])]
    private ?\DateTime $dateMatch = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $niveauRequis = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'matchsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:list', 'game:read'])]
    private ?Utilisateur $createur = null;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'game', orphanRemoval: true)]
    #[Groups(['game:read'])]
    private Collection $participations;

    /**
     * @var Collection<int, Disputer>
     */
    #[ORM\OneToMany(targetEntity: Disputer::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $equipesDisputant;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToOne(mappedBy: 'game', cascade: ['persist', 'remove'])]
    #[Groups(['game:read'])]
    private ?Resultat $resultat = null;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->equipesDisputant = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateMatch(): ?\DateTime
    {
        return $this->dateMatch;
    }

    public function setDateMatch(\DateTime $dateMatch): static
    {
        $this->dateMatch = $dateMatch;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getNiveauRequis(): ?string
    {
        return $this->niveauRequis;
    }

    public function setNiveauRequis(?string $niveauRequis): static
    {
        $this->niveauRequis = $niveauRequis;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreateur(): ?Utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateur $createur): static
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setGame($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getGame() === $this) {
                $participation->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Disputer>
     */
    public function getEquipesDisputant(): Collection
    {
        return $this->equipesDisputant;
    }

    public function addEquipesDisputant(Disputer $equipesDisputant): static
    {
        if (!$this->equipesDisputant->contains($equipesDisputant)) {
            $this->equipesDisputant->add($equipesDisputant);
            $equipesDisputant->setGame($this);
        }

        return $this;
    }

    public function removeEquipesDisputant(Disputer $equipesDisputant): static
    {
        if ($this->equipesDisputant->removeElement($equipesDisputant)) {
            // set the owning side to null (unless already changed)
            if ($equipesDisputant->getGame() === $this) {
                $equipesDisputant->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setGame($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getGame() === $this) {
                $message->setGame(null);
            }
        }

        return $this;
    }

    public function getResultat(): ?Resultat
    {
        return $this->resultat;
    }

    public function setResultat(Resultat $resultat): static
    {
        // set the owning side of the relation if necessary
        if ($resultat->getGame() !== $this) {
            $resultat->setGame($this);
        }

        $this->resultat = $resultat;

        return $this;
    }
}
