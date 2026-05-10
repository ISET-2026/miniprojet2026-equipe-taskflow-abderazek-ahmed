<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Projet;
use App\Entity\User;

class TacheController extends AbstractController
{
    private function sendAssignmentNotification(MailerInterface $mailer, User $assignee, Tache $tache): void
    {
        $email = (new Email())
            ->from('no-reply@taskflow.local')
            ->to($assignee->getEmail())
            ->subject('TaskFlow - Nouvelle assignation de tâche')
            ->text(sprintf(
                "Bonjour %s,\n\nVous avez été assigné à la tâche \"%s\" dans le projet \"%s\".\n\nConnectez-vous pour consulter les détails.\n",
                $assignee->getPseudo(),
                $tache->getTitre(),
                $tache->getProjet()?->getNom()
            ));

        try {
            $mailer->send($email);
        } catch (\Throwable) {
            // Do not block business flow if mail transport is not configured.
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

    private function canAssignTache(Projet $projet): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $createur = $projet->getCreateur();

        return $createur instanceof User && $createur->getId() === $user->getId();
    }

    #[Route('/projets/{id}/taches/nouvelle', name: 'tache_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        Projet $projet,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $tache = new Tache();
        $tache->setProjet($projet);

        $canAssign = $this->canAssignTache($projet);

        $form = $this->createForm(TacheType::class, $tache, [
            'projet' => $projet,
            'can_change_assignee' => $canAssign,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$canAssign) {
                $tache->setAssigneA(null);
            }

            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                $originalFilename = pathinfo($pieceJointeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pieceJointeFile->guessExtension();
                $pieceJointeFile->move($this->getParameter('uploads_directory'), $newFilename);
                $tache->setPieceJointeName($newFilename);
            }

            $em->persist($tache);
            $em->flush();

            if ($tache->getAssigneA() instanceof User) {
                $this->sendAssignmentNotification($mailer, $tache->getAssigneA(), $tache);
            }

            $this->addFlash('success', 'La tâche a été créée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/new.html.twig', [
            'projet' => $projet,
            'form' => $form->createView(),
            'can_assign_tasks' => $canAssign,
        ]);
    }

    #[Route('/taches/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Tache $tache,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if (!$this->canManageTache($tache)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cette tâche.');
            return $this->redirectToRoute('projet_index');
        }

        $projet = $tache->getProjet();
        $canAssign = $this->canAssignTache($projet);
        $assigneAvantModification = $tache->getAssigneA();

        $form = $this->createForm(TacheType::class, $tache, [
            'projet' => $projet,
            'can_change_assignee' => $canAssign,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$canAssign) {
                $tache->setAssigneA($assigneAvantModification);
            }

            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                if ($tache->getPieceJointeName()) {
                    $oldFilePath = $this->getParameter('uploads_directory').'/'.$tache->getPieceJointeName();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $originalFilename = pathinfo($pieceJointeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pieceJointeFile->guessExtension();
                $pieceJointeFile->move($this->getParameter('uploads_directory'), $newFilename);
                $tache->setPieceJointeName($newFilename);
            }

            $em->flush();

            if ($tache->getAssigneA() instanceof User && $tache->getAssigneA() !== $assigneAvantModification) {
                $this->sendAssignmentNotification($mailer, $tache->getAssigneA(), $tache);
            }

            $this->addFlash('success', 'La tâche a été modifiée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/edit.html.twig', [
            'tache' => $tache,
            'form' => $form->createView(),
            'can_assign_tasks' => $canAssign,
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
            $filePath = $this->getParameter('uploads_directory').'/'.$tache->getPieceJointeName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $em->remove($tache);
        $em->flush();

        $this->addFlash('success', 'La tâche a été supprimée avec succès.');

        return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
    }
}
