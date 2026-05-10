<?php

namespace App\Command;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use App\Service\ProjetStatsCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:taskflow:report',
    description: 'Génère un rapport sur l\'état des projets',
)]
class TaskFlowReportCommand extends Command
{
    public function __construct(
        private ProjetRepository $projetRepository,
        private TacheRepository $tacheRepository,
        private UserRepository $userRepository,
        private ProjetStatsCalculator $statsCalculator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'projet',
                'p',
                InputOption::VALUE_OPTIONAL,
                'ID du projet spécifique à analyser'
            )
            ->addOption(
                'overdue',
                null,
                InputOption::VALUE_NONE,
                'Afficher uniquement les projets en retard'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📊 Rapport TaskFlow — État des projets');

        $projets = $this->projetRepository->findAll();
        if (($projetOpt = $input->getOption('projet')) !== null && $projetOpt !== '') {
            $p = $this->projetRepository->find((int) $projetOpt);
            $projets = $p instanceof Projet ? [$p] : [];
            if (!$projets) {
                $io->warning(sprintf('Aucun projet trouvé avec l\'ID "%s".', $projetOpt));
            }
        }

        if ($input->getOption('overdue')) {
            $projets = array_values(array_filter(
                $projets,
                fn (Projet $p) => $this->statsCalculator->isOverdue($p),
            ));

            $io->section('Filtre : projets en retard uniquement');
            if ([] === $projets) {
                $io->success('Aucun projet en retard ne correspond aux critères.');
            } else {
                $io->writeln(sprintf('%d projet(s) en retard (date limite dépassée avec tâches non terminées).', count($projets)));
            }
        }

        $tachesScope = [];
        foreach ($projets as $projet) {
            foreach ($projet->getTaches() as $tache) {
                $tachesScope[] = $tache;
            }
        }

        if ([] === $projets) {
            $io->warning('Pas de projet à inclure dans le rapport (après filtres).');
        }

        $io->section('📈 Statistiques globales (périmètre filtré)');
        $io->writeln([
            'Nombre de projets : <info>' . count($projets) . '</info>',
            'Nombre de tâches : <info>' . count($tachesScope) . '</info>',
        ]);

        $projetsParStatut = [];
        foreach ($projets as $projet) {
            $statut = $projet->getStatut();
            $projetsParStatut[$statut] = ($projetsParStatut[$statut] ?? 0) + 1;
        }

        $io->section('🎯 Répartition des statuts des projets');
        $tableData = [];
        foreach ($projetsParStatut as $statut => $count) {
            $icon = match ($statut) {
                'planifie' => '🔵',
                'en_cours' => '🟡',
                'termine' => '🟢',
                'annule' => '🔴',
                default => '⚪',
            };
            $tableData[] = [$icon . ' ' . ucfirst(str_replace('_', ' ', $statut)), $count];
        }
        if ($tableData) {
            $io->table(['Statut', 'Nombre'], $tableData);
        }

        $tachesParStatut = [];
        foreach ($tachesScope as $tache) {
            $statut = $tache->getStatut();
            $tachesParStatut[$statut] = ($tachesParStatut[$statut] ?? 0) + 1;
        }

        $io->section('✅ Répartition des statuts des tâches');
        $taskRows = [];
        foreach ($tachesParStatut as $statut => $count) {
            $taskRows[] = [ucfirst(str_replace('_', ' ', $statut)), $count];
        }
        if ($taskRows) {
            $io->table(['Statut', 'Nombre'], $taskRows);
        }

        $io->section('⚠️ Projets en retard (date limite + tâches non terminées)');
        $projetsEnRetard = array_values(array_filter(
            $projets,
            fn (Projet $projet) => $this->statsCalculator->isOverdue($projet),
        ));

        if (empty($projetsEnRetard)) {
            $io->success('Aucun projet en retard.');
        } else {
            $rows = [];
            foreach ($projetsEnRetard as $projet) {
                $nonTerminee = $this->tacheRepository->count(['projet' => $projet, 'statut' => 'a_faire'])
                    + $this->tacheRepository->count(['projet' => $projet, 'statut' => 'en_cours']);
                $rows[] = [$projet->getNom(), $projet->getDateLimite()->format('d/m/Y'), $nonTerminee];
            }
            $io->table(['Projet', 'Date limite', 'Tâches non terminées'], $rows);
            $io->warning(count($projetsEnRetard) . ' projet(s) en retard.');
        }

        $io->section('👥 Top 5 des utilisateurs (tâches assignées)');
        $utilisateurs = $this->userRepository->findAll();
        $activiteUtilisateurs = [];
        foreach ($utilisateurs as $user) {
            $activiteUtilisateurs[$user->getPseudo()] = $this->tacheRepository->count(['assigneA' => $user]);
        }

        arsort($activiteUtilisateurs);
        $top5 = array_slice($activiteUtilisateurs, 0, 5, true);

        $tableDataTop = [];
        $rank = 1;
        foreach ($top5 as $pseudo => $count) {
            if ($count === 0) {
                continue;
            }
            $tableDataTop[] = [$rank++, $pseudo, $count];
        }

        if (empty($tableDataTop)) {
            $io->info('Aucune assignation trouvée pour le classement.');
        } else {
            $io->table(['Rang', 'Pseudo', 'Tâches assignées'], $tableDataTop);
        }

        $io->success('Rapport terminé.');

        return Command::SUCCESS;
    }
}
