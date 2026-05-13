<?php

namespace App\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'users')]
#[MongoDB\Index(keys: ['email' => 'asc'], options: ['unique' => true])]
#[MongoDB\Index(keys: ['username' => 'asc'], options: ['unique' => true, 'sparse' => true])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    #[Assert\Length(max: 180, maxMessage: 'Email must be at most {{ limit }} characters.')]
    private ?string $email = null;

    #[MongoDB\Field(type: 'string', nullable: true)]
    #[Assert\Length(min: 2, max: 40)]
    #[Assert\Regex(pattern: '/^[A-Za-z0-9_.-]+$/', message: 'Username can only contain letters, numbers, dots, underscores, and hyphens.')]
    private ?string $username = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $password = null;

    #[MongoDB\Field(type: 'collection')]
    private array $roles = [];

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $username = $username !== null ? trim($username) : null;
        $this->username = $username !== '' ? $username : null;
        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->username) {
            return $this->username;
        }

        return explode('@', (string) $this->email)[0] ?: 'User';
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

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function eraseCredentials(): void
    {
        // Clear any temporary sensitive data
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'displayName' => $this->getDisplayName(),
            'roles' => $this->getRoles(),
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}
