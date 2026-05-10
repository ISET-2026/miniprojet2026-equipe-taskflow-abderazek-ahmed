<?php

namespace App\Repository;

use App\Entity\Etiquette;
use App\Entity\Projet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    public function findByFilters(
        ?string $nom,
        ?string $statut,
        ?User $createur = null,
        ?Etiquette $etiquette = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        if ($nom) {
            $qb->andWhere('LOWER(p.nom) LIKE LOWER(:nom)')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($statut) {
            $qb->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($createur) {
            $qb->andWhere('p.createur = :createur')
                ->setParameter('createur', $createur);
        }

        if ($etiquette) {
            $qb->innerJoin('p.taches', 't')
               ->innerJoin('t.etiquettes', 'e')
               ->andWhere('e = :etiquette')
               ->setParameter('etiquette', $etiquette)
               ->groupBy('p.id'); // Avoid duplicates
        }

        return $qb->orderBy('p.dateCreation', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findMostRecentProjects(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
                    ->orderBy('p.dateCreation', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function getUserById(int $id): ?User
    {
        return $this->getEntityManager()->getRepository(User::class)->find($id);
    }

    public function getEtiquetteById(int $id): ?Etiquette
    {
        return $this->getEntityManager()->getRepository(Etiquette::class)->find($id);
    }
}
