<?php

namespace App\Repository;

use App\Entity\Projet;
use App\Entity\Tache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }

    public function createListingQueryBuilderForProjet(Projet $projet): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->where('t.projet = :projet')
            ->setParameter('projet', $projet)
            ->orderBy('t.dateCreation', 'DESC');
    }
}
