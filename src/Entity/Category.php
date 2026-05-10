<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[ORM\HasLifecycleCallbacks]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: ['service', 'tool', 'logement'], message: 'Type must be service, tool, or logement')]
    private ?string $type = null;

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'category')]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: Tool::class, mappedBy: 'category')]
    private Collection $tools;

    public function __construct()
    {
        $this->services  = new ArrayCollection();
        $this->tools     = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /** @deprecated No 'icon' column in remote DB. Returns null to keep templates working. */
    public function getIcon(): ?string { return null; }
    public function setIcon(?string $icon): static { return $this; }

    /** @deprecated No 'description' column in remote DB. Returns null to keep templates working. */
    public function getDescription(): ?string { return null; }
    public function setDescription(?string $description): static { return $this; }

    /** @return Collection<int, Service> */
    public function getServices(): Collection { return $this->services; }

    /** @return Collection<int, Tool> */
    public function getTools(): Collection { return $this->tools; }

    public function __toString(): string { return $this->name ?? ''; }
}
