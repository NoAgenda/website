<?php

namespace App\Entity;

use App\Repository\UserAccountRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;
use function Symfony\Component\String\u;

#[Entity(repositoryClass: UserAccountRepository::class)]
#[Table(name: 'na_user_account')]
#[UniqueEntity('usernameCanonical', message: 'An account with this username already exists.', errorPath: 'username')]
#[UniqueEntity('emailCanonical', message: 'An account with this email already exists.', errorPath: 'email')]
class UserAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[OneToOne(inversedBy: 'account', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Column(type: 'string', length: 255)]
    private ?string $username = null;

    #[Column(type: 'string', length: 255, unique: true)]
    private ?string $usernameCanonical = null;

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $emailCanonical = null;

    private ?string $plainPassword = null;

    #[Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[Column(type: 'array')]
    private ?array $roles = ['ROLE_USER', 'ROLE_REGISTERED_USER'];

    #[Column(type: 'uuid', nullable: true)]
    private ?string $resetPasswordToken = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public static function canonicalize(string $username): string
    {
        return u((new AsciiSlugger())->slug($username)->toString())->lower();
    }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        $this->usernameCanonical = self::canonicalize($username);

        return $this;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        $this->emailCanonical = u($email)->lower();

        return $this;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;
        $this->password = null;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $key = array_search($role, $this->roles);

        if ($key !== false) {
            unset($this->roles[$key]);
        }

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function generateResetPasswordToken(): self
    {
        $this->resetPasswordToken = Uuid::v4();
        $this->resetPasswordTokenExpiresAt = new \DateTimeImmutable('+1 hour');

        return $this;
    }

    public function isResetPasswordTokenValid(): bool
    {
        return $this->resetPasswordTokenExpiresAt->getTimestamp() > (new \DateTime())->getTimestamp();
    }

    public function clearResetPasswordToken(): self
    {
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles) || in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    public function setAdmin(bool $admin): self
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->roles)) {
            if ($admin) {
                $this->roles = ['ROLE_ADMIN'];
            } else {
                $this->roles = ['ROLE_USER'];
            }
        }

        return $this;
    }

    public function isMod(): bool
    {
        return in_array('ROLE_MOD', $this->roles) || $this->isAdmin();
    }

    public function setMod(bool $mod): self
    {
        if (!$this->isAdmin()) {
            if ($mod) {
                $this->roles = ['ROLE_MOD'];
            } else {
                $this->roles = ['ROLE_USER'];
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
        ]);
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
        ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function __serialize(): array
    {
        return [
            $this->id,
            $this->username,
            $this->password,
        ];
    }

    public function __unserialize($serialized): void
    {
        list (
            $this->id,
            $this->username,
            $this->password,
        ) = $serialized;
    }
}
