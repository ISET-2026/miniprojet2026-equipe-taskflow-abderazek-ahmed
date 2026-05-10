<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Entity\User;
use App\Form\TacheType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TacheController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $tachePieceUploader,
    ) {
    }

    private function sendAssignmentNotification(MailerInterface $mailer, User $assignee, Tache $tache, User $assigner): void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@taskflow.com')
            ->to($assignee->getEmail())
            ->subject('✅ Nouvelle tâche assignée : '.$tache->getTitre())
            ->htmlTemplate('emails/tache_assignee.html.twig')
            ->context([
                'assignee' => $assignee,
                'assigner' => $assigner,
                'tache' => $tache,
                'projet' => $tache->getProjet(),
            ]);

        try {
            $mailer->send($email);
        } catch (\Throwable) {
            // Ne pas bloquer si le transport mail n'est pas configuré (ex. dev).
        }
    }

    private function canManageTache(Tache $tache): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $createur = $tache->getProjet()->getCreateur();
        if ($createur && $createur->getId() === $user->getId()) {
            return true;
        }

        $assigne = $tache->getAssigneA();

        return $assigne instanceof User && $assigne->getId() === $user->getId();
    }

    #[Route('/projets/{id}/taches/nouvelle', name: 'tache_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        Projet $projet,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $tache = new Tache();
        $tache->setProjet($projet);

        $canChangeAssignee = $this->isGranted('ROLE_USER');

        $form = $this->createForm(TacheType::class, $tache, [
            'projet' => $projet,
            'can_change_assignee' => $canChangeAssignee,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                $tache->setPieceJointeName($this->tachePieceUploader->upload($pieceJointeFile));
            }

            $em->persist($tache);
            $em->flush();

            $current = $this->getUser();
            if ($tache->getAssigneA() instanceof User && $current instanceof User) {
                $this->sendAssignmentNotification($mailer, $tache->getAssigneA(), $tache, $current);
            }

            $this->addFlash('success', 'La tâche a été créée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/new.html.twig', [
            'projet' => $projet,
            'form' => $form->createView(),
            'can_assign_tasks' => $canChangeAssignee,
        ]);
    }

    #[Route('/taches/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Tache $tache,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        if (!$this->canManageTache($tache)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cette tâche.');

            return $this->redirectToRoute('projet_index');
        }

        $projet = $tache->getProjet();
        $canChangeAssignee = $this->isGranted('ROLE_USER');
        $assigneAvantModification = $tache->getAssigneA();

        $form = $this->createForm(TacheType::class, $tache, [
            'projet' => $projet,
            'can_change_assignee' => $canChangeAssignee,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                if ($tache->getPieceJointeName()) {
                    $this->tachePieceUploader->remove($tache->getPieceJointeName());
                }
                $tache->setPieceJointeName($this->tachePieceUploader->upload($pieceJointeFile));
            }

            $em->flush();

            $current = $this->getUser();
            if ($tache->getAssigneA() instanceof User
                && $current instanceof User
                && $tache->getAssigneA() !== $assigneAvantModification) {
                $this->sendAssignmentNotification($mailer, $tache->getAssigneA(), $tache, $current);
            }

            $this->addFlash('success', 'La tâche a été modifiée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/edit.html.twig', [
            'tache' => $tache,
            'form' => $form->createView(),
            'can_assign_tasks' => $canChangeAssignee,
        ]);
    }

    #[Route('/taches/{id}/statut', name: 'tache_statut', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function changeStatut(Request $request, Tache $tache, EntityManagerInterface $em): Response
    {
        $projetId = $tache->getProjet()->getId();

        if (!$this->isCsrfTokenValid('statut'.$tache->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('projet_show', ['id' => $projetId]);
        }

        if (!$this->canManageTache($tache)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier le statut de cette tâche.');

            return $this->redirectToRoute('projet_show', ['id' => $projetId]);
        }

        $statut = (string) $request->request->get('statut');
        if (!\in_array($statut, ['a_faire', 'en_cours', 'terminee'], true)) {
            $this->addFlash('error', 'Statut invalide.');

            return $this->redirectToRoute('projet_show', ['id' => $projetId]);
        }

        $tache->setStatut($statut);
        $em->flush();

        $this->addFlash('success', 'Le statut de la tâche a été mis à jour.');

        return $this->redirectToRoute('projet_show', ['id' => $projetId]);
    }

    #[Route('/taches/{id}/supprimer', name: 'tache_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$tache->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('projet_index');
        }

        if (!$this->canManageTache($tache)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer cette tâche.');

            return $this->redirectToRoute('projet_index');
        }

        $projet = $tache->getProjet();

        if ($tache->getPieceJointeName()) {
            $this->tachePieceUploader->remove($tache->getPieceJointeName());
        }

        $em->remove($tache);
        $em->flush();

        $this->addFlash('success', 'La tâche a été supprimée avec succès.');

        return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
    }
}
