<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\User;
use App\Form\ProjetType;
use App\Repository\EtiquetteRepository;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\ProjetStatsCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projets')]
#[IsGranted('PUBLIC_ACCESS')]
class ProjetController extends AbstractController
{
    private const RECENTS_LIMIT = 5;

    private const SESSION_KEY = 'recent_projects';

    public function __construct(
        private readonly ProjetStatsCalculator $projetStatsCalculator,
        private readonly FileUploader $projetImageUploader,
    ) {
    }

    /**
     * @param Projet[] $projects
     */
    private function buildDashboardStats(array $projects): array
    {
        $totalProjects = count($projects);
        $projectsInProgress = 0;
        $projectsDone = 0;
        $totalTasks = 0;
        $doneTasks = 0;

        foreach ($projects as $project) {
            if ($project->getStatut() === 'en_cours') {
                $projectsInProgress++;
            }
            if ($project->getStatut() === 'termine') {
                $projectsDone++;
            }

            foreach ($project->getTaches() as $task) {
                $totalTasks++;
                if ($task->getStatut() === 'terminee') {
                    $doneTasks++;
                }
            }
        }

        $averageProgress = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        return [
            'totalProjects' => $totalProjects,
            'projectsInProgress' => $projectsInProgress,
            'projectsDone' => $projectsDone,
            'totalTasks' => $totalTasks,
            'averageProgress' => $averageProgress,
        ];
    }

    private function isProjetOwnerOrAdmin(Projet $projet): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $createur = $projet->getCreateur();

        return $createur instanceof User && $createur->getId() === $user->getId();
    }

    #[Route(name: 'projet_index', defaults: ['id' => null])]
    #[Route('/{id}', name: 'projet_show', requirements: ['id' => '\d+'])]
    public function index(
        Request $request,
        RequestStack $requestStack,
        ProjetRepository $projetRepository,
        TacheRepository $tacheRepository,
        UserRepository $userRepo,
        EtiquetteRepository $etiquetteRepo,
        PaginatorInterface $paginator,
        ?Projet $projet = null,
    ): Response {
        $session = $requestStack->getSession();

        if ($projet) {
            $recents = $session->get(self::SESSION_KEY, []);
            $projetId = $projet->getId();

            $recents = array_filter($recents, fn ($id) => $id !== $projetId);
            array_unshift($recents, $projetId);
            $recents = array_slice($recents, 0, self::RECENTS_LIMIT);
            $session->set(self::SESSION_KEY, $recents);

            $stats = [
                'progressPercentage' => $this->projetStatsCalculator->getProgressPercentage($projet),
                'taskCountByStatus' => $this->projetStatsCalculator->getTaskCountByStatus($projet),
                'isOverdue' => $this->projetStatsCalculator->isOverdue($projet),
                'remainingDays' => $this->projetStatsCalculator->getRemainingDays($projet),
            ];

            $recentsProjects = [];
            foreach ($recents as $id) {
                $p = $projetRepository->find($id);
                if ($p) {
                    $recentsProjects[] = $p;
                }
            }

            $qbTaches = $tacheRepository->createListingQueryBuilderForProjet($projet);
            $paginationTaches = $paginator->paginate(
                $qbTaches,
                $request->query->getInt('page_taches', 1),
                10,
                [
                    'pageParameterName' => 'page_taches',
                    'defaultSortFieldName' => 't.dateCreation',
                    'defaultSortDirection' => 'desc',
                    'sortFieldAllowList' => ['t.titre', 't.priorite', 't.dateCreation', 't.dateEcheance', 't.statut'],
                    'wrap-queries' => true,
                ]
            );

            return $this->render('projet/show.html.twig', [
                'projet' => $projet,
                'stats' => $stats,
                'recents' => $recentsProjects,
                'paginationTaches' => $paginationTaches,
            ]);
        }

        $nom = $request->query->get('nom');
        $statut = $request->query->get('statut');
        $createurId = $request->query->get('createur');
        $etiquetteId = $request->query->get('etiquette');

        $createur = $createurId ? $userRepo->find((int) $createurId) : null;
        $etiquette = $etiquetteId ? $etiquetteRepo->find((int) $etiquetteId) : null;

        $qb = $projetRepository->createFilteredListingQueryBuilder($nom ?: null, $statut ?: null, $createur, $etiquette);

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            6,
            [
                'defaultSortFieldName' => 'p.dateCreation',
                'defaultSortDirection' => 'desc',
                'sortFieldAllowList' => ['p.nom', 'p.dateCreation', 'p.dateLimite'],
                'distinct' => true,
                'wrap-queries' => true,
            ]
        );

        $filteredForStats = $projetRepository->findByFilters($nom ?: null, $statut ?: null, $createur, $etiquette);
        $users = $userRepo->findAll();
        $etiquettes = $etiquetteRepo->findAll();

        $recentsIds = $session->get(self::SESSION_KEY, []);
        $recents = [];
        foreach ($recentsIds as $id) {
            $p = $projetRepository->find($id);
            if ($p) {
                $recents[] = $p;
            }
        }

        return $this->render('projet/index.html.twig', [
            'pagination' => $pagination,
            'users' => $users,
            'etiquettes' => $etiquettes,
            'recents' => $recents,
            'dashboardStats' => $this->buildDashboardStats($filteredForStats),
        ]);
    }

    #[Route('/nouveau', name: 'projet_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }
            $projet->setCreateur($user);

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $projet->setImageName($this->projetImageUploader->upload($imageFile));
            }

            $em->persist($projet);
            $em->flush();

            $this->addFlash('success', 'Le projet a été créé avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('projet/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'projet_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Projet $projet, EntityManagerInterface $em): Response
    {
        if (!$this->isProjetOwnerOrAdmin($projet)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier ce projet.');

            return $this->redirectToRoute('projet_index');
        }

        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                if ($projet->getImageName()) {
                    $this->projetImageUploader->remove($projet->getImageName());
                }
                $projet->setImageName($this->projetImageUploader->upload($imageFile));
            }

            $em->flush();

            $this->addFlash('success', 'Le projet a été modifié avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('projet/edit.html.twig', [
            'projet' => $projet,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'projet_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Projet $projet, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$projet->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('projet_index');
        }

        if (!$this->isProjetOwnerOrAdmin($projet)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer ce projet.');

            return $this->redirectToRoute('projet_index');
        }

        if ($projet->getImageName()) {
            $this->projetImageUploader->remove($projet->getImageName());
        }

        $em->remove($projet);
        $em->flush();

        $this->addFlash('success', 'Le projet a été supprimé avec succès.');

        return $this->redirectToRoute('projet_index');
    }
}
