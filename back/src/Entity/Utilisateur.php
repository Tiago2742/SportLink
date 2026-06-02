<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['utilisateur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['utilisateur:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['utilisateur:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['utilisateur:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['utilisateur:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['utilisateur:read'])]
    private ?string $localisation = null;

    /**
     * @var Collection<int, UtilisateurSport>
     */
    #[ORM\OneToMany(targetEntity: UtilisateurSport::class, mappedBy: 'utilisateur', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['utilisateur_sport:read'])]
    private Collection $sports;

    #[ORM\Column]
    private ?\DateTime $dateInscription = null;

    /**
     * @var Collection<int, Equipe>
     */
    #[ORM\OneToMany(targetEntity: Equipe::class, mappedBy: 'createur')]
    private Collection $equipesCreees;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'createur')]
    private Collection $matchsCrees;

    /**
     * @var Collection<int, EquipeJoueur>
     */
    #[ORM\OneToMany(targetEntity: EquipeJoueur::class, mappedBy: 'utilisateur')]
    private Collection $equipesJoueur;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    private Collection $participations;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'expediteur', orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->equipesCreees = new ArrayCollection();
        $this->matchsCrees = new ArrayCollection();
        $this->equipesJoueur = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->sports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getemail(): ?string
    {
        return $this->email;
    }

    public function setemail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getDateInscription(): ?\DateTime
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTime $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    /**
     * @return Collection<int, Equipe>
     */
    public function getEquipesCreees(): Collection
    {
        return $this->equipesCreees;
    }

    public function addEquipesCreee(Equipe $equipesCreee): static
    {
        if (!$this->equipesCreees->contains($equipesCreee)) {
            $this->equipesCreees->add($equipesCreee);
            $equipesCreee->setCreateur($this);
        }

        return $this;
    }

    public function removeEquipesCreee(Equipe $equipesCreee): static
    {
        if ($this->equipesCreees->removeElement($equipesCreee)) {
            // set the owning side to null (unless already changed)
            if ($equipesCreee->getCreateur() === $this) {
                $equipesCreee->setCreateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getMatchsCrees(): Collection
    {
        return $this->matchsCrees;
    }

    public function addMatchsCree(Game $matchsCree): static
    {
        if (!$this->matchsCrees->contains($matchsCree)) {
            $this->matchsCrees->add($matchsCree);
            $matchsCree->setCreateur($this);
        }

        return $this;
    }

    public function removeMatchsCree(Game $matchsCree): static
    {
        if ($this->matchsCrees->removeElement($matchsCree)) {
            // set the owning side to null (unless already changed)
            if ($matchsCree->getCreateur() === $this) {
                $matchsCree->setCreateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EquipeJoueur>
     */
    public function getEquipesJoueur(): Collection
    {
        return $this->equipesJoueur;
    }

    public function addEquipesJoueur(EquipeJoueur $equipesJoueur): static
    {
        if (!$this->equipesJoueur->contains($equipesJoueur)) {
            $this->equipesJoueur->add($equipesJoueur);
            $equipesJoueur->setUtilisateur($this);
        }

        return $this;
    }

    public function removeEquipesJoueur(EquipeJoueur $equipesJoueur): static
    {
        if ($this->equipesJoueur->removeElement($equipesJoueur)) {
            // set the owning side to null (unless already changed)
            if ($equipesJoueur->getUtilisateur() === $this) {
                $equipesJoueur->setUtilisateur(null);
            }
        }

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
            $participation->setUtilisateur($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getUtilisateur() === $this) {
                $participation->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UtilisateurSport>
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    public function addSport(UtilisateurSport $sport): static
    {
        if (!$this->sports->contains($sport)) {
            $this->sports->add($sport);
            $sport->setUtilisateur($this);
        }

        return $this;
    }

    public function removeSport(UtilisateurSport $sport): static
    {
        $this->sports->removeElement($sport);

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
            $message->setExpediteur($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getExpediteur() === $this) {
                $message->setExpediteur(null);
            }
        }

        return $this;
    }
}
