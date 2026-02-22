<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(
    fields: ['email'],
    message: 'Cet email est déjà utilisé.'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_ACTIVE])]
    private string $accountStatus = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $suspiciousActivityScore = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastFailedLoginAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $suspendedUntil = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $selfieImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identityDocumentImage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $faceVerifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deactivatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reactivationRequestedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reactivationRequestNote = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // used to clear temporary sensitive data if any
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getAccountStatus(): string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(string $accountStatus): self
    {
        $allowed = [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_BANNED];
        if (!in_array($accountStatus, $allowed, true)) {
            $accountStatus = self::STATUS_ACTIVE;
        }

        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->accountStatus === self::STATUS_BANNED;
    }

    public function isSuspended(): bool
    {
        if ($this->accountStatus !== self::STATUS_SUSPENDED) {
            return false;
        }

        if ($this->suspendedUntil === null) {
            return true;
        }

        return $this->suspendedUntil > new \DateTime();
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function getSuspiciousActivityScore(): int
    {
        return $this->suspiciousActivityScore;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function getLastFailedLoginAt(): ?\DateTimeInterface
    {
        return $this->lastFailedLoginAt;
    }

    public function getSuspendedUntil(): ?\DateTimeInterface
    {
        return $this->suspendedUntil;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName !== null ? trim($firstName) : null;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName !== null ? trim($lastName) : null;
        return $this;
    }

    // Compatibility aliases for legacy code using nom/prenom.
    public function getNom(): ?string
    {
        return $this->lastName;
    }

    public function setNom(?string $nom): self
    {
        return $this->setLastName($nom);
    }

    public function getPrenom(): ?string
    {
        return $this->firstName;
    }

    public function setPrenom(?string $prenom): self
    {
        return $this->setFirstName($prenom);
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone !== null ? trim($phone) : null;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar !== null ? trim($avatar) : null;
        return $this;
    }

    public function getName(): ?string
    {
        $parts = array_filter([$this->firstName, $this->lastName], static fn (?string $v): bool => $v !== null && $v !== '');
        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return $this->email ? explode('@', $this->email)[0] : null;
    }

    public function getSelfieImage(): ?string
    {
        return $this->selfieImage;
    }

    public function setSelfieImage(?string $selfieImage): self
    {
        $this->selfieImage = $selfieImage !== null ? trim($selfieImage) : null;
        return $this;
    }

    public function getIdentityDocumentImage(): ?string
    {
        return $this->identityDocumentImage;
    }

    public function setIdentityDocumentImage(?string $identityDocumentImage): self
    {
        $this->identityDocumentImage = $identityDocumentImage !== null ? trim($identityDocumentImage) : null;
        return $this;
    }

    public function getFaceVerifiedAt(): ?\DateTimeInterface
    {
        return $this->faceVerifiedAt;
    }

    public function setFaceVerifiedAt(?\DateTimeInterface $faceVerifiedAt): self
    {
        $this->faceVerifiedAt = $this->toMutableDateTime($faceVerifiedAt);
        return $this;
    }

    public function getDeactivatedAt(): ?\DateTimeInterface
    {
        return $this->deactivatedAt;
    }

    public function getReactivationRequestedAt(): ?\DateTimeInterface
    {
        return $this->reactivationRequestedAt;
    }

    public function getReactivationRequestNote(): ?string
    {
        return $this->reactivationRequestNote;
    }

    public function isDeactivatedByUser(): bool
    {
        return $this->deactivatedAt !== null;
    }

    public function hasPendingReactivationRequest(): bool
    {
        return $this->deactivatedAt !== null && $this->reactivationRequestedAt !== null;
    }

    public function deactivateByUser(): self
    {
        $this->accountStatus = self::STATUS_SUSPENDED;
        $this->suspendedUntil = null;
        $this->deactivatedAt = new \DateTime();
        $this->reactivationRequestedAt = null;
        $this->reactivationRequestNote = null;
        return $this;
    }

    public function requestReactivation(?string $note = null): self
    {
        $this->reactivationRequestedAt = new \DateTime();
        $trimmed = $note !== null ? trim($note) : null;
        $this->reactivationRequestNote = $trimmed !== '' ? $trimmed : null;
        return $this;
    }

    public function suspend(?\DateTimeInterface $until = null): self
    {
        $this->accountStatus = self::STATUS_SUSPENDED;
        $this->suspendedUntil = $this->toMutableDateTime($until);
        $this->suspiciousActivityScore = min(999, $this->suspiciousActivityScore + 10);
        return $this;
    }

    public function ban(): self
    {
        $this->accountStatus = self::STATUS_BANNED;
        $this->suspendedUntil = null;
        return $this;
    }

    public function activateAccount(): self
    {
        $this->accountStatus = self::STATUS_ACTIVE;
        $this->suspendedUntil = null;
        $this->failedLoginAttempts = 0;
        $this->deactivatedAt = null;
        $this->reactivationRequestedAt = null;
        $this->reactivationRequestNote = null;
        return $this;
    }

    public function recordFailedLogin(): self
    {
        $this->failedLoginAttempts++;
        $this->lastFailedLoginAt = new \DateTime();
        $this->suspiciousActivityScore = min(999, $this->suspiciousActivityScore + 1);
        return $this;
    }

    public function recordSuccessfulLogin(?string $ipAddress): self
    {
        $this->lastLoginAt = new \DateTime();
        $this->lastLoginIp = $ipAddress ? substr($ipAddress, 0, 45) : null;
        $this->failedLoginAttempts = 0;
        if ($this->suspiciousActivityScore > 0) {
            $this->suspiciousActivityScore--;
        }
        return $this;
    }

    private function toMutableDateTime(?\DateTimeInterface $value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        return \DateTime::createFromInterface($value);
    }
}
