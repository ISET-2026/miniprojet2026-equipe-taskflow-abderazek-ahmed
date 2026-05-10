<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use App\Service\EmailService;
use App\Service\FileUploader;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Exemple d'intégration du service EmailService et FileUploader
 * dans TacheController
 */
#[Route('/taches')]
class TacheControllerExample extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TacheRepository $tacheRepository,
        private EmailService $emailService,
        #[Autowire(service: 'file_uploader_taches')]
        private FileUploader $fileUploader,
    ) {}

    /**
     * Créer une nouvelle tâche avec upload de pièce jointe
     * et envoi d'email de notification
     */
    #[Route('/{id}/nouvelle', name: 'tache_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])]
        $projet,
    ): Response {
        $tache = new Tache();
        $tache->setProjet($projet);

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la pièce jointe
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                $pieceJointeName = $this->fileUploader->upload($pieceJointeFile);
                $tache->setPieceJointeName($pieceJointeName);
            }

            // Assigner la tâche à un utilisateur
            $assignee = $form->get('assignee')->getData();
            if ($assignee) {
                $tache->setAssigneA($assignee);

                // 📧 Envoyer un email de notification
                $this->emailService->sendTaskAssignmentEmail(
                    $tache,
                    $assignee,
                    $this->getUser() // L'assignateur (utilisateur courant)
                );

                $this->addFlash('success', '✅ Email de notification envoyé à ' . $assignee->getPseudo());
            }

            $this->entityManager->persist($tache);
            $this->entityManager->flush();

            $this->addFlash('success', '✅ Tâche créée avec succès !');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/create.html.twig', [
            'form' => $form->createView(),
            'projet' => $projet,
        ]);
    }

    /**
     * Modifier une tâche avec gestion de la pièce jointe
     */
    #[Route('/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Tache $tache,
    ): Response {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload d'une nouvelle pièce jointe
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                // Supprimer l'ancienne pièce jointe si elle existe
                if ($tache->getPieceJointeName()) {
                    $this->fileUploader->remove($tache->getPieceJointeName());
                }

                // Uploader la nouvelle
                $pieceJointeName = $this->fileUploader->upload($pieceJointeFile);
                $tache->setPieceJointeName($pieceJointeName);
            }

            $this->entityManager->flush();

            $this->addFlash('success', '✅ Tâche modifiée avec succès !');
            return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/edit.html.twig', [
            'form' => $form->createView(),
            'tache' => $tache,
        ]);
    }

    /**
     * Supprimer une tâche et son pièce jointe
     */
    #[Route('/{id}/supprimer', name: 'tache_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request,
        Tache $tache,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $tache->getId(), $request->request->get('_token'))) {
            // Supprimer la pièce jointe du système de fichiers
            if ($tache->getPieceJointeName()) {
                $this->fileUploader->remove($tache->getPieceJointeName());
            }

            $projetId = $tache->getProjet()->getId();
            $this->entityManager->remove($tache);
            $this->entityManager->flush();

            $this->addFlash('success', '✅ Tâche supprimée !');
            return $this->redirectToRoute('projet_show', ['id' => $projetId]);
        }

        return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
    }
}
