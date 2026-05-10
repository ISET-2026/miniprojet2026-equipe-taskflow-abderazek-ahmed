<?php

namespace App\Service;

use App\Entity\Projet;
use App\Repository\TacheRepository;

class ProjetStatsCalculator
{
    public function __construct(private TacheRepository $tacheRepository)
    {
    }

    public function getProgressPercentage(Projet $projet): int
    {
        $taches = $projet->getTaches();
        $total = count($taches);

        if ($total === 0) {
            return 0;
        }

        $completed = 0;
        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'terminee') {
                $completed++;
            }
        }

        return (int) round(($completed / $total) * 100);
    }

    public function getTaskCountByStatus(Projet $projet): array
    {
        $result = [
            'a_faire' => 0,
            'en_cours' => 0,
            'terminee' => 0,
        ];

        foreach ($projet->getTaches() as $tache) {
            $statut = $tache->getStatut();
            if (isset($result[$statut])) {
                $result[$statut]++;
            }
        }

        return $result;
    }

    public function isOverdue(Projet $projet): bool
    {
        $now = new \DateTimeImmutable();
        $dateLimite = $projet->getDateLimite();

        if (!$dateLimite) {
            return false;
        }

        // Check if there are incomplete tasks
        foreach ($projet->getTaches() as $tache) {
            if ($tache->getStatut() !== 'terminee') {
                return $dateLimite < $now;
            }
        }

        return false;
    }

    public function getRemainingDays(Projet $projet): int
    {
        $dateLimite = $projet->getDateLimite();

        if (!$dateLimite) {
            return PHP_INT_MAX;
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($dateLimite);

        return (int) $interval->format('%r%a');
    }
}
