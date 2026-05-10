<?php

namespace App\Entity;

use App\Repository\TacheRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['tache:read']],
    denormalizationContext: ['groups' => ['tache:write']],
    security: "is_granted('PUBLIC_ACCESS')",
    securityMessage: "Accès refusé."
)]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['tache:read', 'tache:write', 'projet:read'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotNull]
    #[Assert\Choice(choices: ['basse', 'moyenne', 'haute', 'urgente'])]
    #[Groups(['tache:read', 'tache:write'])]
    private ?string $priorite = 'moyenne';

    #[ORM\Column(length: 20)]
    #[Assert\NotNull]
    #[Assert\Choice(choices: ['a_faire', 'en_cours', 'terminee'])]
    #[Groups(['tache:read', 'tache:write', 'projet:read'])]
    private ?string $statut = 'a_faire';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    #[Groups(['tache:read'])]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?\DateTimeImmutable $dateEcheance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tache:read'])]
    private ?string $pieceJointeName = null;

    #[ORM\ManyToOne(inversedBy: 'taches')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['tache:read', 'tache:write'])]
    private ?Projet $projet = null;

    #[ORM\ManyToOne(inversedBy: 'taches')]
    #[Groups(['tache:read', 'tache:write'])]
    private ?User $assigneA = null;

    #[ORM\ManyToMany(targetEntity: Etiquette::class, inversedBy: 'taches')]
    #[ORM\JoinTable(name: 'tache_etiquette')]
    #[ORM\JoinColumn(name: 'tache_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'etiquette_id', referencedColumnName: 'id')]
    #[Groups(['tache:read', 'tache:write'])]
    private Collection $etiquettes;

    public function __construct()
    {
        $this->etiquettes = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->titre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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

    public function getDateEcheance(): ?\DateTimeImmutable
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeImmutable $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;

        return $this;
    }

    public function getPieceJointeName(): ?string
    {
        return $this->pieceJointeName;
    }

    public function setPieceJointeName(?string $pieceJointeName): static
    {
        $this->pieceJointeName = $pieceJointeName;

        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getAssigneA(): ?User
    {
        return $this->assigneA;
    }

    public function setAssigneA(?User $assigneA): static
    {
        $this->assigneA = $assigneA;

        return $this;
    }

    /**
     * @return Collection<int, Etiquette>
     */
    public function getEtiquettes(): Collection
    {
        return $this->etiquettes;
    }

    public function addEtiquette(Etiquette $etiquette): static
    {
        if (!$this->etiquettes->contains($etiquette)) {
            $this->etiquettes->add($etiquette);
            $etiquette->addTache($this);
        }

        return $this;
    }

    public function removeEtiquette(Etiquette $etiquette): static
    {
        if ($this->etiquettes->removeElement($etiquette)) {
            $etiquette->removeTache($this);
        }

        return $this;
    }
}
