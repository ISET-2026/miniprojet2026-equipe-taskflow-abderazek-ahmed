<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\User;
use App\Form\ProjetType;
use App\Repository\EtiquetteRepository;
use App\Repository\ProjetRepository;
use App\Repository\UserRepository;
use App\Service\ProjetStatsCalculator;
use Doctrine\ORM\EntityManagerInterface;
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
    ) {
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
    public function index(Request $request, RequestStack $requestStack, ProjetRepository $projetRepository, UserRepository $userRepo, EtiquetteRepository $etiquetteRepo, ?Projet $projet = null): Response
    {
        $session = $requestStack->getSession();

        // If an id is provided, show project details
        if ($projet) {
            // Add to recent projects in session
            $recents = $session->get(self::SESSION_KEY, []);
            $projetId = $projet->getId();

            // Remove duplicates and add to beginning
            $recents = array_filter($recents, fn($id) => $id !== $projetId);
            array_unshift($recents, $projetId);
            $recents = array_slice($recents, 0, self::RECENTS_LIMIT);
            $session->set(self::SESSION_KEY, $recents);

            // Calculate stats (service injecté par constructeur)
            $stats = [
                'progressPercentage' => $this->projetStatsCalculator->getProgressPercentage($projet),
                'taskCountByStatus' => $this->projetStatsCalculator->getTaskCountByStatus($projet),
                'isOverdue' => $this->projetStatsCalculator->isOverdue($projet),
                'remainingDays' => $this->projetStatsCalculator->getRemainingDays($projet),
            ];

            // Get recent projects for sidebar
            $recentsProjects = [];
            foreach ($recents as $id) {
                $p = $projetRepository->find($id);
                if ($p) {
                    $recentsProjects[] = $p;
                }
            }

            return $this->render('projet/show.html.twig', [
                'projet' => $projet,
                'stats' => $stats,
                'recents' => $recentsProjects,
            ]);
        }

        // List all projects with filters
        $nom = $request->query->get('nom');
        $statut = $request->query->get('statut');
        $createurId = $request->query->get('createur');
        $etiquetteId = $request->query->get('etiquette');

        $createur = $createurId ? $userRepo->find($createurId) : null;
        $etiquette = $etiquetteId ? $etiquetteRepo->find($etiquetteId) : null;

        $projects = $projetRepository->findByFilters($nom, $statut ?: null, $createur, $etiquette);
        $users = $userRepo->findAll();
        $etiquettes = $etiquetteRepo->findAll();

        // Get recent projects for sidebar
        $recentsIds = $session->get(self::SESSION_KEY, []);
        $recents = [];
        foreach ($recentsIds as $id) {
            $p = $projetRepository->find($id);
            if ($p) {
                $recents[] = $p;
            }
        }

        return $this->render('projet/index.html.twig', [
            'projets' => $projects,
            'users' => $users,
            'etiquettes' => $etiquettes,
            'recents' => $recents,
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
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $projet->setImageName($newFilename);
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
                    $oldImagePath = $this->getParameter('images_directory').'/'.$projet->getImageName();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $projet->setImageName($newFilename);
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
            $imagePath = $this->getParameter('images_directory').'/'.$projet->getImageName();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $em->remove($projet);
        $em->flush();

        $this->addFlash('success', 'Le projet a été supprimé avec succès.');

        return $this->redirectToRoute('projet_index');
    }
}
