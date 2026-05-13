<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends ServiceDocumentRepository<User>
 */
class UserRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => trim($username)]);
    }

    public function save(User $user, bool $flush = true): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist($user);
        if ($flush) {
            $dm->flush();
        }
    }
}
