<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Logement::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Logement $logement = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $locataire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\OneToOne(mappedBy: 'reservation', targetEntity: Avis::class, cascade: ['persist', 'remove'])]
    private ?Avis $avis = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombrePersonnes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notesSpeciales = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->statut = 'en_attente';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): static
    {
        $this->logement = $logement;
        return $this;
    }

    public function getLocataire(): ?User
    {
        return $this->locataire;
    }

    public function setLocataire(?User $locataire): static
    {
        $this->locataire = $locataire;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;
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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setAvis(?Avis $avis): static
    {
        // set the owning side of the relation if necessary
        if ($avis !== null && $avis->getReservation() !== $this) {
            $avis->setReservation($this);
        }

        $this->avis = $avis;
        return $this;
    }

    /**
     * Vérifie si la réservation est terminée
     */
    public function isTerminee(): bool
    {
        $now = new \DateTime();
        return $this->dateFin < $now;
    }

    /**
     * Vérifie si la réservation peut être annulée (plus de 3 jours avant l'arrivée)
     */
    public function peutEtreAnnulee(): bool
    {
        $now = new \DateTime();
        $dateDebut = clone $this->dateDebut;
        $dateDebut->modify('-3 days');
        
        return $now < $dateDebut && $this->statut === 'confirmee';
    }

    /**
     * Calcule le nombre de nuits
     */
    public function getNombreNuits(): int
    {
        $interval = $this->dateDebut->diff($this->dateFin);
        return $interval->days;
    }

    public function getNombrePersonnes(): ?int
    {
        return $this->nombrePersonnes;
    }

    public function setNombrePersonnes(?int $nombrePersonnes): static
    {
        $this->nombrePersonnes = $nombrePersonnes;
        return $this;
    }

    public function getNotesSpeciales(): ?string
    {
        return $this->notesSpeciales;
    }

    public function setNotesSpeciales(?string $notesSpeciales): static
    {
        $this->notesSpeciales = $notesSpeciales;
        return $this;
    }
}
