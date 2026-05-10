<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED    = 'banned';

    // ── Core auth ─────────────────────────────────────────────────────────

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** JSON array of Symfony roles e.g. ["ROLE_ADMIN"] */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    // ── Profile ───────────────────────────────────────────────────────────

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $name = null;

    /** Last name (desktop app: nom) */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    /** First name (desktop app: prenom) */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenom = null;

    /** Single varchar role used by desktop app */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(name: 'account_status', length: 30)]
    private string $accountStatus = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'is_verified')]
    private bool $isVerified = true;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'profile_image', length: 255, nullable: true)]
    private ?string $profileImage = null;

    // ── Tokens ────────────────────────────────────────────────────────────

    #[ORM\Column(name: 'session_token', length: 255, nullable: true)]
    private ?string $sessionToken = null;

    #[ORM\Column(name: 'host_request_date', nullable: true)]
    private ?\DateTimeImmutable $hostRequestDate = null;

    #[ORM\Column(name: 'confirmation_token', length: 255, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(name: 'confirmation_token_expires', nullable: true)]
    private ?\DateTimeImmutable $confirmationTokenExpires = null;

    #[ORM\Column(name: 'reset_sms_code', length: 10, nullable: true)]
    private ?string $resetSmsCode = null;

    #[ORM\Column(name: 'reset_sms_expires', nullable: true)]
    private ?\DateTimeImmutable $resetSmsExpires = null;

    // ── OAuth ─────────────────────────────────────────────────────────────

    #[ORM\Column(name: 'oauth_provider', length: 30, nullable: true)]
    private ?string $oauthProvider = null;

    #[ORM\Column(name: 'oauth_id', length: 100, nullable: true)]
    private ?string $oauthId = null;

    // ── Face / identity ───────────────────────────────────────────────────

    #[ORM\Column(name: 'selfie_image', length: 255, nullable: true)]
    private ?string $selfieImage = null;

    #[ORM\Column(name: 'identity_document_image', length: 255, nullable: true)]
    private ?string $identityDocumentImage = null;

    #[ORM\Column(name: 'face_verified_at', nullable: true)]
    private ?\DateTimeImmutable $faceVerifiedAt = null;

    // ── Timestamps ────────────────────────────────────────────────────────

    #[ORM\Column(name: 'created_at', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

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

    // ── UserInterface ─────────────────────────────────────────────────────

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return (string) $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    // ── Status helpers ────────────────────────────────────────────────────

    public function isBanned(): bool
    {
        return $this->accountStatus === self::STATUS_BANNED;
    }

    public function isSuspended(): bool
    {
        return $this->accountStatus === self::STATUS_SUSPENDED;
    }

    public function isActive(): bool
    {
        return $this->accountStatus === self::STATUS_ACTIVE;
    }

    public function ban(): static
    {
        $this->accountStatus = self::STATUS_BANNED;
        return $this;
    }

    public function suspend(): static
    {
        $this->accountStatus = self::STATUS_SUSPENDED;
        return $this;
    }

    public function activateAccount(): static
    {
        $this->accountStatus = self::STATUS_ACTIVE;
        return $this;
    }

    /** Compatibility: deactivated = suspended with no expiry */
    public function isDeactivatedByUser(): bool
    {
        return $this->accountStatus === self::STATUS_SUSPENDED;
    }

    public function requestReactivation(?string $note = null): static
    {
        // No-op: reactivation columns don't exist in remote DB.
        // Admin can manually set accountStatus back to active.
        return $this;
    }

    // ── Getters & Setters ─────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }

    /** Alias used by AdminController */
    public function getFirstName(): ?string { return $this->prenom; }
    public function setFirstName(?string $v): static { $this->prenom = $v; return $this; }

    /** Alias used by AdminController */
    public function getLastName(): ?string { return $this->nom; }
    public function setLastName(?string $v): static { $this->nom = $v; return $this; }

    /** Alias: profile_image column, used by AdminController as avatar */
    public function getAvatar(): ?string { return $this->profileImage; }
    public function setAvatar(?string $avatar): static { $this->profileImage = $avatar; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getAccountStatus(): string { return $this->accountStatus; }
    public function setAccountStatus(string $accountStatus): static { $this->accountStatus = $accountStatus; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getProfileImage(): ?string { return $this->profileImage; }
    public function setProfileImage(?string $profileImage): static { $this->profileImage = $profileImage; return $this; }

    public function getSessionToken(): ?string { return $this->sessionToken; }
    public function setSessionToken(?string $sessionToken): static { $this->sessionToken = $sessionToken; return $this; }

    public function getHostRequestDate(): ?\DateTimeImmutable { return $this->hostRequestDate; }
    public function setHostRequestDate(?\DateTimeImmutable $d): static { $this->hostRequestDate = $d; return $this; }

    public function getConfirmationToken(): ?string { return $this->confirmationToken; }
    public function setConfirmationToken(?string $t): static { $this->confirmationToken = $t; return $this; }

    public function getConfirmationTokenExpires(): ?\DateTimeImmutable { return $this->confirmationTokenExpires; }
    public function setConfirmationTokenExpires(?\DateTimeImmutable $d): static { $this->confirmationTokenExpires = $d; return $this; }

    public function getResetSmsCode(): ?string { return $this->resetSmsCode; }
    public function setResetSmsCode(?string $code): static { $this->resetSmsCode = $code; return $this; }

    public function getResetSmsExpires(): ?\DateTimeImmutable { return $this->resetSmsExpires; }
    public function setResetSmsExpires(?\DateTimeImmutable $d): static { $this->resetSmsExpires = $d; return $this; }

    public function getOauthProvider(): ?string { return $this->oauthProvider; }
    public function setOauthProvider(?string $v): static { $this->oauthProvider = $v; return $this; }

    public function getOauthId(): ?string { return $this->oauthId; }
    public function setOauthId(?string $v): static { $this->oauthId = $v; return $this; }

    public function getSelfieImage(): ?string { return $this->selfieImage; }
    public function setSelfieImage(?string $v): static { $this->selfieImage = $v; return $this; }

    public function getIdentityDocumentImage(): ?string { return $this->identityDocumentImage; }
    public function setIdentityDocumentImage(?string $v): static { $this->identityDocumentImage = $v; return $this; }

    public function getFaceVerifiedAt(): ?\DateTimeImmutable { return $this->faceVerifiedAt; }
    public function setFaceVerifiedAt(?\DateTimeImmutable $d): static { $this->faceVerifiedAt = $d; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    /** No 'suspicious_activity_score' column in remote DB. Returns 0. */
    public function getSuspiciousActivityScore(): int { return 0; }

    /** No 'failed_login_attempts' column in remote DB. Returns 0. */
    public function getFailedLoginAttempts(): int { return 0; }

    /** No 'last_login_at' column in remote DB. Returns null. */
    public function getLastLoginAt(): ?\DateTimeInterface { return null; }

    /** No 'last_login_ip' column in remote DB. Returns null. */
    public function getLastLoginIp(): ?string { return null; }

    /** No 'last_failed_login_at' column in remote DB. Returns null. */
    public function getLastFailedLoginAt(): ?\DateTimeInterface { return null; }

    /** No 'suspended_until' column in remote DB. Returns null. */
    public function getSuspendedUntil(): ?\DateTimeInterface { return null; }

    /** No 'deactivated_at' column in remote DB. Returns null. */
    public function getDeactivatedAt(): ?\DateTimeInterface { return null; }

    /** No 'reactivation_requested_at' column in remote DB. Returns null. */
    public function getReactivationRequestedAt(): ?\DateTimeInterface { return null; }

    /** No 'reactivation_request_note' column in remote DB. Returns null. */
    public function getReactivationRequestNote(): ?string { return null; }

    /** No reactivation columns in remote DB. Returns false. */
    public function hasPendingReactivationRequest(): bool { return false; }

    public function __toString(): string
    {
        return $this->name ?? $this->email ?? '';
    }
}