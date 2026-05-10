<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Logement::class)]
    #[ORM\JoinColumn(name: 'logement_id', nullable: false)]
    private ?Logement $logement = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'locataire_id', nullable: false)]
    private ?User $locataire = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'nombre_personnes', nullable: true)]
    private ?int $nombrePersonnes = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->statut = 'en_attente';
    }

    public function getId(): ?int { return $this->id; }

    public function getLogement(): ?Logement { return $this->logement; }
    public function setLogement(?Logement $logement): static { $this->logement = $logement; return $this; }

    public function getLocataire(): ?User { return $this->locataire; }
    public function setLocataire(?User $locataire): static { $this->locataire = $locataire; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getMontantTotal(): ?string { return $this->montantTotal; }
    public function setMontantTotal(string $montantTotal): static { $this->montantTotal = $montantTotal; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getNombrePersonnes(): ?int { return $this->nombrePersonnes; }
    public function setNombrePersonnes(?int $nombrePersonnes): static { $this->nombrePersonnes = $nombrePersonnes; return $this; }

    public function isTerminee(): bool
    {
        return $this->dateFin !== null && $this->dateFin < new \DateTime();
    }

    public function peutEtreAnnulee(): bool
    {
        if ($this->dateDebut === null || $this->statut !== 'confirmee') {
            return false;
        }
        $limit = (clone $this->dateDebut)->modify('-3 days');
        return new \DateTime() < $limit;
    }

    public function getNombreNuits(): int
    {
        if ($this->dateDebut === null || $this->dateFin === null) {
            return 0;
        }
        return (int) $this->dateDebut->diff($this->dateFin)->days;
    }
}
