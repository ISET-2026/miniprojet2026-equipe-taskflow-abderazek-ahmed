<?php

namespace App\Service;

use App\Entity\Projet;

class ProjetStatsCalculator
{
    public function __construct()
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
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $dateLimite = $projet->getDateLimite();

        if (!$dateLimite) {
            return false;
        }

        // Overdue only if deadline is passed and at least one task is not completed.
        foreach ($projet->getTaches() as $tache) {
            if ($tache->getStatut() !== 'terminee') {
                return $dateLimite < $today;
            }
        }

        return false;
    }

    public function getRemainingDays(Projet $projet): int
    {
        $dateLimite = $projet->getDateLimite();

        if (!$dateLimite) {
            return 0;
        }

        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $interval = $today->diff($dateLimite);

        return (int) $interval->format('%r%a');
    }
}
