<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<Comment>
 */
class CommentRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findByVideoId(string $videoId): array
    {
        return $this->findBy(['videoId' => $videoId], ['createdAt' => 'DESC']);
    }

    public function save(Comment $comment, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($comment);
        if ($flush) {
            $dm->flush();
        }
    }

    public function remove(Comment $comment, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->remove($comment);
        if ($flush) {
            $dm->flush();
        }
    }
}
