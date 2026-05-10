<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
#[ORM\Table(name: 'logement')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // DB column: name — kept as $titre in PHP for template compatibility
    #[ORM\Column(name: 'name', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // DB column: address — kept as $adresse in PHP for template compatibility
    #[ORM\Column(name: 'address', length: 255, nullable: true)]
    private ?string $adresse = null;

    // DB column: price_per_night — kept as $prixParNuit in PHP for template compatibility
    #[ORM\Column(name: 'price_per_night', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixParNuit = null;

    // DB column: number_of_rooms — kept as $nombreChambres in PHP for template compatibility
    #[ORM\Column(name: 'number_of_rooms', nullable: true)]
    private ?int $nombreChambres = null;

    #[ORM\Column(name: 'is_active')]
    private ?bool $isActive = false;

    #[ORM\Column(name: 'image_name', length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(name: 'image_size', nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(name: 'image_updated_at', nullable: true)]
    private ?\DateTimeImmutable $imageUpdatedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'number_of_beds', nullable: true)]
    private ?int $numberOfBeds = null;

    #[ORM\Column(name: 'number_of_bathrooms', nullable: true)]
    private ?int $numberOfBathrooms = null;

    #[ORM\Column(name: 'max_guests', nullable: true)]
    private ?int $maxGuests = null;

    #[ORM\Column(name: 'square_meters', nullable: true)]
    private ?int $squareMeters = null;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // DB FK: host_id — kept as $proprietaire in PHP for template compatibility
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'host_id', nullable: false)]
    private ?User $proprietaire = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true)]
    private ?Category $category = null;

    #[Vich\UploadableField(mapping: 'logement_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    // ── titre / name ──────────────────────────────────────────────────────
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getName(): ?string { return $this->titre; }
    public function setName(string $name): static { $this->titre = $name; return $this; }

    // ── description ───────────────────────────────────────────────────────
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    // ── adresse / address ─────────────────────────────────────────────────
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }
    public function getAddress(): ?string { return $this->adresse; }
    public function setAddress(?string $address): static { $this->adresse = $address; return $this; }

    // ── prixParNuit / pricePerNight ───────────────────────────────────────
    public function getPrixParNuit(): ?string { return $this->prixParNuit; }
    public function setPrixParNuit(string $prixParNuit): static { $this->prixParNuit = $prixParNuit; return $this; }
    public function getPricePerNight(): ?string { return $this->prixParNuit; }
    public function setPricePerNight(string $pricePerNight): static { $this->prixParNuit = $pricePerNight; return $this; }

    // ── nombreChambres / numberOfRooms ────────────────────────────────────
    public function getNombreChambres(): ?int { return $this->nombreChambres; }
    public function setNombreChambres(?int $nombreChambres): static { $this->nombreChambres = $nombreChambres; return $this; }
    public function getNumberOfRooms(): ?int { return $this->nombreChambres; }
    public function setNumberOfRooms(?int $n): static { $this->nombreChambres = $n; return $this; }

    // ── isActive ──────────────────────────────────────────────────────────
    public function isActive(): ?bool { return $this->isActive; }
    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    // ── image (non-mapped — kept as null alias for template compatibility) ─
    /** @deprecated No 'image' column in DB. Use imageName instead. */
    public function getImage(): ?string { return $this->imageName; }
    public function setImage(?string $image): static { return $this; }

    // ── type (non-mapped — no column in DB) ───────────────────────────────
    /** @deprecated No 'type' column in DB. Returns null. */
    public function getType(): ?string { return null; }
    public function setType(?string $type): static { return $this; }

    // ── capacite (non-mapped — no column in DB, use maxGuests instead) ────
    /** @deprecated No 'capacite' column in DB. Use maxGuests instead. */
    public function getCapacite(): ?int { return $this->maxGuests; }
    public function setCapacite(?int $capacite): static { $this->maxGuests = $capacite; return $this; }

    // ── disponible (non-mapped — no column in DB, use isActive instead) ───
    /** @deprecated No 'disponible' column in DB. Use isActive instead. */
    public function isDisponible(): ?bool { return $this->isActive; }
    public function setDisponible(bool $disponible): static { $this->isActive = $disponible; return $this; }

    // ── proprietaire / host ───────────────────────────────────────────────
    public function getProprietaire(): ?User { return $this->proprietaire; }
    public function setProprietaire(?User $proprietaire): static { $this->proprietaire = $proprietaire; return $this; }
    public function getHost(): ?User { return $this->proprietaire; }
    public function setHost(?User $host): static { $this->proprietaire = $host; return $this; }

    // ── city / country ────────────────────────────────────────────────────
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }
    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): static { $this->country = $country; return $this; }

    // ── other fields ──────────────────────────────────────────────────────
    public function getNumberOfBeds(): ?int { return $this->numberOfBeds; }
    public function setNumberOfBeds(?int $n): static { $this->numberOfBeds = $n; return $this; }

    public function getNumberOfBathrooms(): ?int { return $this->numberOfBathrooms; }
    public function setNumberOfBathrooms(?int $n): static { $this->numberOfBathrooms = $n; return $this; }

    public function getMaxGuests(): ?int { return $this->maxGuests; }
    public function setMaxGuests(?int $n): static { $this->maxGuests = $n; return $this; }

    public function getSquareMeters(): ?int { return $this->squareMeters; }
    public function setSquareMeters(?int $n): static { $this->squareMeters = $n; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    // ── image upload ──────────────────────────────────────────────────────
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->imageUpdatedAt = new \DateTimeImmutable();
        }
    }
    public function getImageFile(): ?File { return $this->imageFile; }
    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): static { $this->imageName = $imageName; return $this; }
    public function getImageSize(): ?int { return $this->imageSize; }
    public function setImageSize(?int $imageSize): static { $this->imageSize = $imageSize; return $this; }
    public function getImageUpdatedAt(): ?\DateTimeImmutable { return $this->imageUpdatedAt; }
    public function setImageUpdatedAt(?\DateTimeImmutable $imageUpdatedAt): static { $this->imageUpdatedAt = $imageUpdatedAt; return $this; }

    // ── helpers ───────────────────────────────────────────────────────────
    public function getFullLocation(): string
    {
        $parts = array_filter([$this->city, $this->country]);
        return implode(', ', $parts) ?: ($this->adresse ?? '');
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([$this->adresse, $this->city, $this->country]);
        return implode(', ', $parts);
    }
}
