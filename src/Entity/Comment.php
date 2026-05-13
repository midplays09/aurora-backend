<?php

namespace App\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'comments')]
#[MongoDB\Index(keys: ['videoId' => 'asc'])]
class Comment
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $userId = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $userEmail = null;

    #[MongoDB\Field(type: 'string', nullable: true)]
    private ?string $username = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $videoId = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Comment text cannot be empty.')]
    #[Assert\Length(max: 1000)]
    private ?string $text = null;

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): static
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getVideoId(): ?string
    {
        return $this->videoId;
    }

    public function setVideoId(string $videoId): static
    {
        $this->videoId = $videoId;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'userEmail' => $this->userEmail,
            'username' => $this->username,
            'displayName' => $this->username ?: (explode('@', (string) $this->userEmail)[0] ?: 'User'),
            'videoId' => $this->videoId,
            'text' => $this->text,
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}
