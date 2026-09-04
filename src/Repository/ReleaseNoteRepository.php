<?php

namespace App\Repository;

use App\Entity\ReleaseNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReleaseNote>
 */
class ReleaseNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReleaseNote::class);
    }

    /**
     * @param string[] $versions
     * @return array<string, ReleaseNote> Indexed by version string
     */
    public function findByVersionsIndexed(array $versions): array
    {
        if (empty($versions)) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->where('r.version IN (:versions)')
            ->setParameter('versions', $versions);

        /** @var ReleaseNote[] $results */
        $results = $qb->getQuery()->getResult();
        $indexed = [];

        foreach ($results as $note) {
            $indexed[(string) $note->getVersion()] = $note;
        }

        return $indexed;
    }
}
