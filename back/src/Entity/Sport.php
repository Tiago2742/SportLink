<?php

namespace App\Entity;

use App\Enum\TypeSport;
use App\Repository\SportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SportRepository::class)]
#[UniqueEntity('nom')]
class Sport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sport:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['sport:read'])]
    private ?string $nom = null;

    #[ORM\Column(enumType: TypeSport::class)]
    #[Groups(['sport:read'])]
    private ?TypeSport $type = null;

    #[ORM\OneToMany(mappedBy: 'sport', targetEntity: Niveau::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['sport:niveaux'])]
    private Collection $niveaux;

    public function __construct()
    {
        $this->niveaux = new ArrayCollection();
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

    public function getType(): ?TypeSport
    {
        return $this->type;
    }

    public function setType(TypeSport $type): static
    {
        $this->type = $type;
        return $this;
    }

    /** @return Collection<int, Niveau> */
    public function getNiveaux(): Collection
    {
        return $this->niveaux;
    }

    public function addNiveau(Niveau $niveau): static
    {
        if (!$this->niveaux->contains($niveau)) {
            $this->niveaux->add($niveau);
            $niveau->setSport($this);
        }
        return $this;
    }

    public function removeNiveau(Niveau $niveau): static
    {
        if ($this->niveaux->removeElement($niveau)) {
            if ($niveau->getSport() === $this) {
                $niveau->setSport(null);
            }
        }
        return $this;
    }
}
