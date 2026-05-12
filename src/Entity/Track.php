<?php

namespace App\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'tracks')]
#[MongoDB\Index(keys: ['userId' => 'asc'])]
#[MongoDB\Index(keys: ['categoryId' => 'asc'])]
#[MongoDB\Index(keys: ['userId' => 'asc', 'title' => 'asc', 'artist' => 'asc'])]
class Track
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Track title is required.')]
    #[Assert\Length(max: 300, maxMessage: 'Title must be at most {{ limit }} characters.')]
    private ?string $title = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Artist is required.')]
    #[Assert\Length(max: 300, maxMessage: 'Artist must be at most {{ limit }} characters.')]
    private ?string $artist = null;

    #[MongoDB\Field(type: 'string', nullable: true)]
    #[Assert\Length(max: 300, maxMessage: 'Album must be at most {{ limit }} characters.')]
    private ?string $album = null;

    #[MongoDB\Field(type: 'float')]
    private float $duration = 0;

    #[MongoDB\Field(type: 'string', nullable: true)]
    private ?string $categoryId = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $userId = null;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;
        return $this;
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album): static
    {
        $this->album = $album;
        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): static
    {
        $this->categoryId = $categoryId;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'album' => $this->album,
            'duration' => $this->duration,
            'categoryId' => $this->categoryId,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt?->format('c'),
        ];
    }
}
