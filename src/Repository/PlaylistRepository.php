<?php

namespace App\Repository;

use App\Entity\Playlist;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<Playlist>
 */
class PlaylistRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    public function save(Playlist $playlist, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($playlist);
        if ($flush) {
            $dm->flush();
        }
    }

    public function remove(Playlist $playlist, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->remove($playlist);
        if ($flush) {
            $dm->flush();
        }
    }
}
