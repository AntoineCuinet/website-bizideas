<?php

namespace App\Repository;

use App\Entity\BusinessIdea;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusinessIdea>
 */
class BusinessIdeaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessIdea::class);
    }

    /**
     * Returns all business ideas visible to a given user:
     * - All non-draft ideas (public to all authenticated users)
     * - Draft ideas created by the given user
     *
     * @return BusinessIdea[]
     */
    public function findVisibleForUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status != :draft OR b.creator = :user')
            ->setParameter('draft', BusinessIdea::STATUS_DRAFT)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
