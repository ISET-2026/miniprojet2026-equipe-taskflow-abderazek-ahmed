<?php

namespace App\Tests\Service;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Service\ProjetStatsCalculator;
use PHPUnit\Framework\TestCase;

class ProjetStatsCalculatorTest extends TestCase
{
    public function testGetProgressPercentageIsZeroWithoutCompletedTasks(): void
    {
        $calc = new ProjetStatsCalculator($this->createMock(\App\Repository\TacheRepository::class));

        $projet = new Projet();
        $t = new Tache();
        $t->setStatut('a_faire');
        $t->setPriorite('moyenne');
        $projet->addTache($t);

        self::assertSame(0, $calc->getProgressPercentage($projet));
    }

    public function testGetProgressPercentageIsOneHundredWhenAllTasksCompleted(): void
    {
        $calc = new ProjetStatsCalculator($this->createMock(\App\Repository\TacheRepository::class));

        $projet = new Projet();
        foreach (['terminee', 'terminee'] as $statut) {
            $t = new Tache();
            $t->setStatut($statut);
            $t->setPriorite('basse');
            $projet->addTache($t);
        }

        self::assertSame(100, $calc->getProgressPercentage($projet));
    }

    public function testIsOverdueTrueWhenDeadlinePassedAndRemainingTasks(): void
    {
        $calc = new ProjetStatsCalculator($this->createMock(\App\Repository\TacheRepository::class));

        $projet = new Projet();
        $projet->setDateLimite(new \DateTimeImmutable('-5 days'));

        $t = new Tache();
        $t->setStatut('a_faire');
        $t->setPriorite('haute');
        $projet->addTache($t);

        self::assertTrue($calc->isOverdue($projet));
    }

    public function testGetRemainingDaysIsNegativeWhenOverdueDeadline(): void
    {
        $calc = new ProjetStatsCalculator($this->createMock(\App\Repository\TacheRepository::class));

        $projet = new Projet();
        $projet->setDateLimite(new \DateTimeImmutable('-3 days'));

        self::assertLessThan(0, $calc->getRemainingDays($projet));
    }
}
