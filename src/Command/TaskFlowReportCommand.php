<?php

namespace App\Command;

use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
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

        $io->title('📊 Rapport TaskFlow - État des Projets');

        // Récupérer les projets à analyser
        $projets = $this->projetRepository->findAll();
        $taches = $this->tacheRepository->findAll();

        // Statistiques globales
        $io->section('📈 Statistiques Globales');
        $io->writeln([
            'Nombre total de projets : <info>' . count($projets) . '</info>',
            'Nombre total de tâches : <info>' . count($taches) . '</info>',
        ]);

        // Répartition des statuts des projets
        $io->section('🎯 Répartition des Statuts des Projets');
        $projetsParStatut = [];
        foreach ($projets as $projet) {
            $statut = $projet->getStatut();
            $projetsParStatut[$statut] = ($projetsParStatut[$statut] ?? 0) + 1;
        }

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
        $io->table(['Statut', 'Nombre'], $tableData);

        // Répartition des statuts des tâches
        $io->section('✅ Répartition des Statuts des Tâches');
        $tachesParStatut = [];
        foreach ($taches as $tache) {
            $statut = $tache->getStatut();
            $tachesParStatut[$statut] = ($tachesParStatut[$statut] ?? 0) + 1;
        }

        $tableData = [];
        foreach ($tachesParStatut as $statut => $count) {
            $tableData[] = [ucfirst(str_replace('_', ' ', $statut)), $count];
        }
        $io->table(['Statut', 'Nombre'], $tableData);

        // Projets en retard
        $io->section('⚠️ Projets en Retard');
        $projetsEnRetard = [];
        foreach ($projets as $projet) {
            $now = new \DateTime();
            $dateLimit = $projet->getDateLimite();
            $taskNonTerminees = $this->tacheRepository->findBy([
                'projet' => $projet,
                'statut' => 'a_faire',
            ]);

            if ($dateLimit < $now && count($taskNonTerminees) > 0) {
                $projetsEnRetard[] = $projet;
            }
        }

        if (empty($projetsEnRetard)) {
            $io->success('✅ Aucun projet en retard !');
        } else {
            $tableData = [];
            foreach ($projetsEnRetard as $projet) {
                $daysOverdue = $projet->getDateLimite()->diff(new \DateTime())->days;
                $nonTerminees = $this->tacheRepository->count([
                    'projet' => $projet,
                    'statut' => 'a_faire',
                ]);
                $tableData[] = [
                    $projet->getNom(),
                    $projet->getDateLimite()->format('d/m/Y'),
                    $daysOverdue . ' jour(s)',
                    $nonTerminees,
                ];
            }
            $io->table(['Projet', 'Date Limite', 'Retard', 'Tâches Non Terminées'], $tableData);
            $io->warning(count($projetsEnRetard) . ' projet(s) en retard !');
        }

        // Top 5 des utilisateurs les plus actifs
        $io->section('👥 Top 5 des Utilisateurs les Plus Actifs');
        $utilisateurs = $this->userRepository->findAll();
        $activiteUtilisateurs = [];
        foreach ($utilisateurs as $user) {
            $countTaches = $this->tacheRepository->count(['assigneA' => $user]);
            if ($countTaches > 0) {
                $activiteUtilisateurs[$user->getPseudo()] = $countTaches;
            }
        }

        if (empty($activiteUtilisateurs)) {
            $io->info('Aucune tâche assignée aux utilisateurs.');
        } else {
            arsort($activiteUtilisateurs);
            $top5 = array_slice($activiteUtilisateurs, 0, 5, true);
            $tableData = [];
            $rank = 1;
            foreach ($top5 as $pseudo => $count) {
                $tableData[] = [$rank++, $pseudo, $count];
            }
            $io->table(['Rang', 'Utilisateur', 'Tâches Assignées'], $tableData);
        }

        $io->newLine();
        $io->info('📊 Rapport généré avec succès !');

        return Command::SUCCESS;
    }
}
