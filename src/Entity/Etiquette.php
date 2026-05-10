<?php

namespace App\Entity;

use App\Repository\EtiquetteRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EtiquetteRepository::class)]
#[UniqueEntity(fields: ['nom'], message: 'Une étiquette avec ce nom existe déjà.')]
#[ApiResource(
    normalizationContext: ['groups' => ['etiquette:read']],
    denormalizationContext: ['groups' => ['etiquette:write']],
    security: "is_granted('ROLE_ADMIN')",
    securityMessage: "Accès réservé aux administrateurs."
)]
class Etiquette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etiquette:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['etiquette:read', 'etiquette:write'])]
    private ?string $nom = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Le code couleur doit être au format hexadécimal (#RRGGBB)')]
    #[Groups(['etiquette:read', 'etiquette:write'])]
    private ?string $couleur = null;

    #[ORM\ManyToMany(targetEntity: Tache::class, mappedBy: 'etiquettes')]
    #[Groups(['etiquette:read'])]
    private Collection $taches;

    public function __construct()
    {
        $this->taches = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom;
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

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * @return Collection<int, Tache>
     */
    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTache(Tache $tache): static
    {
        if (!$this->taches->contains($tache)) {
            $this->taches->add($tache);
            $tache->addEtiquette($this);
        }

        return $this;
    }

    public function removeTache(Tache $tache): static
    {
        if ($this->taches->removeElement($tache)) {
            $tache->removeEtiquette($this);
        }

        return $this;
    }
}
