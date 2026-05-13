<?php

namespace App\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'track_stats')]
#[MongoDB\Index(keys: ['videoId' => 'asc'], options: ['unique' => true])]
class TrackStat
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $videoId = null;

    #[MongoDB\Field(type: 'int')]
    private int $totalViews = 0;

    #[MongoDB\Field(type: 'int')]
    private int $totalWatchTimeSeconds = 0;

    #[MongoDB\Field(type: 'int')]
    private int $totalLikes = 0;

    #[MongoDB\Field(type: 'date')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function recordView(int $watchTimeSeconds): static
    {
        $this->totalViews++;
        $this->totalWatchTimeSeconds += max(0, $watchTimeSeconds);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'videoId' => $this->videoId,
            'totalViews' => $this->totalViews,
            'totalWatchTimeSeconds' => $this->totalWatchTimeSeconds,
            'totalLikes' => $this->totalLikes,
            'updatedAt' => $this->updatedAt?->format('c'),
        ];
    }
}
