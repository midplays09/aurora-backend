<?php

namespace App\Repository;

use App\Document\Category;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<Category>
 */
class CategoryRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return Category[]
     */
    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['name' => 'asc']);
    }

    public function save(Category $category, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($category);
        if ($flush) {
            $dm->flush();
        }
    }

    public function remove(Category $category, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->remove($category);
        if ($flush) {
            $dm->flush();
        }
    }
}
