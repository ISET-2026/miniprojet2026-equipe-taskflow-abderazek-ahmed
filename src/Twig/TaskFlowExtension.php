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
    public function timeAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTimeImmutable();
        $mutable = \DateTime::createFromInterface($date);
        $interval = $now->diff($mutable);

        if ($interval->days === 0) {
            if ($interval->h === 0) {
                $m = max(0, $interval->i);

                return 'il y a ' . $m . ' minute' . ($m > 1 ? 's' : '');
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
        $p = max(0, min(100, $percentage));

        if ($p > 75) {
            $barClasses = 'progress-bar bg-success text-white';
            $extraStyle = '';
        } elseif ($p > 50) {
            $barClasses = 'progress-bar bg-warning text-dark';
            $extraStyle = '';
        } elseif ($p > 25) {
            $barClasses = 'progress-bar text-white';
            $extraStyle = 'background-color:#fd7e14;';
        } else {
            $barClasses = 'progress-bar bg-danger text-white';
            $extraStyle = '';
        }

        return sprintf(
            '<div class="progress"><div class="%s" role="progressbar" style="width: %d%%;%s" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100">%d%%</div></div>',
            $barClasses,
            $p,
            $extraStyle,
            $p,
            $p
        );
    }
}
