<?php

namespace App\Entity;

use App\Enum\StatutGame;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:list', 'game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:list', 'game:read'])]
    private ?Sport $sport = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?Niveau $niveauRequis = null;

    #[ORM\Column]
    #[Groups(['game:list', 'game:read'])]
    private ?\DateTime $dateMatch = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $lieu = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['game:read'])]
    private ?string $description = null;

    #[ORM\Column(enumType: StatutGame::class)]
    #[Groups(['game:list', 'game:read'])]
    private ?StatutGame $statut = null;

    #[ORM\ManyToOne(inversedBy: 'matchsCrees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:list', 'game:read'])]
    private ?Utilisateur $createur = null;

    /** @var Collection<int, MatchCamp> */
    #[ORM\OneToMany(mappedBy: 'game', targetEntity: MatchCamp::class, orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['game:list', 'game:read'])]
    private Collection $camps;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToOne(mappedBy: 'game', cascade: ['persist', 'remove'])]
    #[Groups(['game:read'])]
    private ?Resultat $resultat = null;

    public function __construct()
    {
        $this->camps    = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNiveauRequis(): ?Niveau
    {
        return $this->niveauRequis;
    }

    public function setNiveauRequis(?Niveau $niveauRequis): static
    {
        $this->niveauRequis = $niveauRequis;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatut(): ?StatutGame
    {
        return $this->statut;
    }

    public function setStatut(StatutGame $statut): static
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

    /** @return Collection<int, MatchCamp> */
    public function getCamps(): Collection
    {
        return $this->camps;
    }

    #[Groups(['game:list', 'game:read'])]
    public function getNombreCamps(): int
    {
        return $this->camps->count();
    }

    public function addCamp(MatchCamp $camp): static
    {
        if (!$this->camps->contains($camp)) {
            $this->camps->add($camp);
            $camp->setGame($this);
        }
        return $this;
    }

    public function removeCamp(MatchCamp $camp): static
    {
        $this->camps->removeElement($camp);
        return $this;
    }

    /** @return Collection<int, Message> */
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
        if ($resultat->getGame() !== $this) {
            $resultat->setGame($this);
        }
        $this->resultat = $resultat;
        return $this;
    }
}
