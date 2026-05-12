<?php

namespace App\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'playlists')]
#[MongoDB\Index(keys: ['userId' => 'asc'])]
class Playlist
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Playlist name is required.')]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $userId = null;

    #[MongoDB\Field(type: 'collection')]
    private array $trackIds = [];

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $createdAt = null;

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->trackIds = [];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getTrackIds(): array
    {
        return $this->trackIds;
    }

    public function setTrackIds(array $trackIds): static
    {
        $this->trackIds = $trackIds;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function addTrackId(string $trackId): static
    {
        if (!in_array($trackId, $this->trackIds)) {
            $this->trackIds[] = $trackId;
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function removeTrackId(string $trackId): static
    {
        $this->trackIds = array_filter($this->trackIds, fn($id) => $id !== $trackId);
        $this->trackIds = array_values($this->trackIds);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'userId' => $this->userId,
            'trackIds' => $this->trackIds,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ];
    }
}
