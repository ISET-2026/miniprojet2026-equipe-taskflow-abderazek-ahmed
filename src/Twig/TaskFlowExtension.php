<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TaskFlowExtension extends AbstractExtension
{
    /**
     * Retourne les filtres personnalisés
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_ago', [$this, 'timeAgo']),
            new TwigFilter('priority_icon', [$this, 'priorityIcon']),
        ];
    }

    /**
     * Retourne les fonctions personnalisées
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('progress_bar', [$this, 'progressBar'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Filtre : convertit une date en format relatif "il y a X jours"
     */
    public function timeAgo(\DateTime $date): string
    {
        $now = new \DateTime();
        $interval = $now->diff($date);

        if ($interval->days == 0) {
            if ($interval->h == 0) {
                return 'il y a ' . $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
            }
            return 'il y a ' . $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
        }

        if ($interval->days == 1) {
            return 'hier';
        }

        if ($interval->days < 7) {
            return 'il y a ' . $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
        }

        if ($interval->days < 30) {
            $weeks = floor($interval->days / 7);
            return 'il y a ' . $weeks . ' semaine' . ($weeks > 1 ? 's' : '');
        }

        if ($interval->days < 365) {
            $months = floor($interval->days / 30);
            return 'il y a ' . $months . ' mois' . ($months > 1 ? 's' : '');
        }

        $years = floor($interval->days / 365);
        return 'il y a ' . $years . ' an' . ($years > 1 ? 's' : '');
    }

    /**
     * Filtre : retourne un icône emoji selon la priorité
     */
    public function priorityIcon(string $priorite): string
    {
        return match ($priorite) {
            'basse' => '🔵',
            'moyenne' => '🟢',
            'haute' => '🟠',
            'urgente' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Fonction : génère une barre de progression HTML Bootstrap
     */
    public function progressBar(int $percentage): string
    {
        // Déterminer la couleur selon le pourcentage
        if ($percentage >= 75) {
            $color = 'bg-success';
        } elseif ($percentage >= 50) {
            $color = 'bg-info';
        } elseif ($percentage >= 25) {
            $color = 'bg-warning';
        } else {
            $color = 'bg-danger';
        }

        return sprintf(
            '<div class="progress"><div class="progress-bar %s" role="progressbar" style="width: %d%%" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100">%d%%</div></div>',
            $color,
            $percentage,
            $percentage,
            $percentage
        );
    }
}
