<?php

namespace App\Entity;

use App\Repository\CovoiturageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CovoiturageRepository::class)]
#[ORM\Table(name: 'covoiturages')]
class Covoiturage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    // DB: ville_depart — kept as $depart for template compatibility
    #[ORM\Column(name: 'ville_depart', length: 120)]
    #[Assert\NotBlank]
    private ?string $depart = null;

    // DB: ville_arrivee — kept as $destination for template compatibility
    #[ORM\Column(name: 'ville_arrivee', length: 120)]
    #[Assert\NotBlank]
    private ?string $destination = null;

    #[ORM\Column(name: 'date_depart', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: 'heure_depart', type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(name: 'prix_par_place')]
    private ?float $prixParPlace = null;

    // DB: nb_places — kept as $places for template compatibility
    #[ORM\Column(name: 'nb_places')]
    #[Assert\Positive]
    private ?int $places = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'conducteur_id', nullable: false)]
    private ?User $conducteur = null;

    public function getId(): ?int { return $this->id; }

    // ── depart / villeDepart ──────────────────────────────────────────────
    public function getDepart(): ?string { return $this->depart; }
    public function setDepart(string $depart): static { $this->depart = $depart; return $this; }
    public function getVilleDepart(): ?string { return $this->depart; }
    public function setVilleDepart(string $v): static { $this->depart = $v; return $this; }

    // ── destination / villeArrivee ────────────────────────────────────────
    public function getDestination(): ?string { return $this->destination; }
    public function setDestination(string $destination): static { $this->destination = $destination; return $this; }
    public function getVilleArrivee(): ?string { return $this->destination; }
    public function setVilleArrivee(string $v): static { $this->destination = $v; return $this; }

    // ── dateDepart ────────────────────────────────────────────────────────
    public function getDateDepart(): ?\DateTimeInterface { return $this->dateDepart; }
    public function setDateDepart(\DateTimeInterface $dateDepart): static { $this->dateDepart = $dateDepart; return $this; }

    // ── heureDepart ───────────────────────────────────────────────────────
    public function getHeureDepart(): ?\DateTimeInterface { return $this->heureDepart; }
    public function setHeureDepart(\DateTimeInterface $heureDepart): static { $this->heureDepart = $heureDepart; return $this; }

    // ── prixParPlace ──────────────────────────────────────────────────────
    public function getPrixParPlace(): ?float { return $this->prixParPlace; }
    public function setPrixParPlace(float $prixParPlace): static { $this->prixParPlace = $prixParPlace; return $this; }

    // ── places / nbPlaces ─────────────────────────────────────────────────
    public function getPlaces(): ?int { return $this->places; }
    public function setPlaces(int $places): static { $this->places = $places; return $this; }
    public function getNbPlaces(): ?int { return $this->places; }
    public function setNbPlaces(int $n): static { $this->places = $n; return $this; }

    // ── commentaire ───────────────────────────────────────────────────────
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    // ── conducteur ────────────────────────────────────────────────────────
    public function getConducteur(): ?User { return $this->conducteur; }
    public function setConducteur(?User $conducteur): static { $this->conducteur = $conducteur; return $this; }
}
