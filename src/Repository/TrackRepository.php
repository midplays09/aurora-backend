<?php

namespace App\Repository;

use App\Document\Track;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<Track>
 */
class TrackRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Track::class);
    }

    /**
     * @return Track[]
     */
    public function findByUserId(string $userId, ?string $categoryId = null): array
    {
        $criteria = ['userId' => $userId];
        if ($categoryId !== null) {
            $criteria['categoryId'] = $categoryId;
        }
        return $this->findBy($criteria, ['title' => 'asc']);
    }

    public function save(Track $track, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($track);
        if ($flush) {
            $dm->flush();
        }
    }

    public function remove(Track $track, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->remove($track);
        if ($flush) {
            $dm->flush();
        }
    }

    /**
     * Remove category reference from all tracks in a category (when category is deleted).
     */
    public function unsetCategoryForAll(string $categoryId): void
    {
        $dm = $this->getDocumentManager();
        $dm->createQueryBuilder(Track::class)
            ->updateMany()
            ->field('categoryId')->equals($categoryId)
            ->field('categoryId')->set(null)
            ->getQuery()
            ->execute();
    }
}
