<?php

namespace App\Repository;

use App\Entity\TrackStat;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<TrackStat>
 */
class TrackStatRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackStat::class);
    }

    public function findByVideoId(string $videoId): ?TrackStat
    {
        return $this->findOneBy(['videoId' => $videoId]);
    }

    public function save(TrackStat $stat, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($stat);
        if ($flush) {
            $dm->flush();
        }
    }
}
