<?php

namespace App\Entity;

use App\Repository\FavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\Table(name: 'favorites')]
#[ORM\UniqueConstraint(name: 'uk_favorite', columns: ['guest_id', 'listing_id', 'listing_type'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'guest_id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'listing_type', length: 20)]
    private ?string $itemType = null;

    #[ORM\Column(name: 'listing_id')]
    private ?int $itemId = null;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getItemType(): ?string { return $this->itemType; }
    public function setItemType(string $itemType): static { $this->itemType = $itemType; return $this; }

    public function getItemId(): ?int { return $this->itemId; }
    public function setItemId(int $itemId): static { $this->itemId = $itemId; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
