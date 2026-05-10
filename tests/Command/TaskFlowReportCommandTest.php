<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TaskFlowReportCommandTest extends KernelTestCase
{
    public function testExecuteWithSuccess(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:taskflow:report');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('📊 Rapport TaskFlow', $output);
        $this->assertStringContainsString('Statistiques Globales', $output);
        $this->assertStringContainsString('Répartition des Statuts', $output);

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
