<?php

namespace App\Repository;

use App\Entity\Etiquette;
use App\Entity\Projet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    public function createFilteredListingQueryBuilder(
        ?string $nom,
        ?string $statut,
        ?User $createur = null,
        ?Etiquette $etiquette = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.createur', 'createur')->addSelect('createur');

        $this->applyFilters($qb, $nom, $statut, $createur, $etiquette);

        return $qb->orderBy('p.dateCreation', 'DESC');
    }

    public function findByFilters(
        ?string $nom,
        ?string $statut,
        ?User $createur = null,
        ?Etiquette $etiquette = null
    ): array {
        return $this->createFilteredListingQueryBuilder($nom, $statut, $createur, $etiquette)
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(
        QueryBuilder $qb,
        ?string $nom,
        ?string $statut,
        ?User $createur,
        ?Etiquette $etiquette,
    ): void {
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
            $qb->distinct()
                ->innerJoin('p.taches', 't_filtre')
                ->innerJoin('t_filtre.etiquettes', 'e_filtre')
                ->andWhere('e_filtre = :etiquette')
                ->setParameter('etiquette', $etiquette);
        }
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
