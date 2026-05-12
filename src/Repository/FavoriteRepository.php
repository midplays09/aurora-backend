<?php

namespace App\Repository;

use App\Entity\Favorite;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<Favorite>
 */
class FavoriteRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    public function findOneByUserAndVideo(string $userId, string $videoId): ?Favorite
    {
        return $this->findOneBy(['userId' => $userId, 'videoId' => $videoId]);
    }

    public function save(Favorite $favorite, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($favorite);
        if ($flush) {
            $dm->flush();
        }
    }

    public function remove(Favorite $favorite, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->remove($favorite);
        if ($flush) {
            $dm->flush();
        }
    }
}
