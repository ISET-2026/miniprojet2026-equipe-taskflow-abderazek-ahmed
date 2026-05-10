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
        if ($projet->getId() === null) {
            return $this->getProgressPercentageFromCollection($projet);
        }

        $total = $this->tacheRepository->count(['projet' => $projet]);
        if ($total === 0) {
            return 0;
        }

        $completed = $this->tacheRepository->count(['projet' => $projet, 'statut' => 'terminee']);

        return (int) round(($completed / $total) * 100);
    }

    public function getTaskCountByStatus(Projet $projet): array
    {
        $result = [
            'a_faire' => 0,
            'en_cours' => 0,
            'terminee' => 0,
        ];

        if ($projet->getId() === null) {
            foreach ($projet->getTaches() as $tache) {
                $statut = $tache->getStatut();
                if (isset($result[$statut])) {
                    $result[$statut]++;
                }
            }

            return $result;
        }

        foreach (array_keys($result) as $statut) {
            $result[$statut] = $this->tacheRepository->count(['projet' => $projet, 'statut' => $statut]);
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

        if ($dateLimite >= $today) {
            return false;
        }

        if ($projet->getId() === null) {
            return $this->hasNonCompletedTask($projet);
        }

        $incomplete = $this->tacheRepository->count(['projet' => $projet, 'statut' => 'a_faire'])
            + $this->tacheRepository->count(['projet' => $projet, 'statut' => 'en_cours']);

        return $incomplete > 0;
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

    private function getProgressPercentageFromCollection(Projet $projet): int
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

    private function hasNonCompletedTask(Projet $projet): bool
    {
        foreach ($projet->getTaches() as $tache) {
            if ($tache->getStatut() !== 'terminee') {
                return true;
            }
        }

        return false;
    }
}
