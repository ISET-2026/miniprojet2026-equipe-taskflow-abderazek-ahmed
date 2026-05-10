<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Projet;

#[IsGranted('ROLE_USER')]
class TacheController extends AbstractController
{
    #[Route('/projets/{id}/taches/nouvelle', name: 'tache_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Projet $projet,
        EntityManagerInterface $em
    ): Response {
        $tache = new Tache();
        $tache->setProjet($projet);

        $form = $this->createForm(TacheType::class, $tache, [
            'projet' => $projet,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                $originalFilename = pathinfo($pieceJointeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', null, $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pieceJointeFile->guessExtension();
                $pieceJointeFile->move($this->getParameter('uploads_directory'), $newFilename);
                $tache->setPieceJointeName($newFilename);
            }

            $em->persist($tache);
            $em->flush();

            $this->addFlash('success', 'La tâche a été créée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('tache/new.html.twig', [
            'projet' => $projet,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/taches/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Tache $tache,
        EntityManagerInterface $em
    ): Response {
        if ($tache->getProjet()->getCreateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cette tâche.');
            return $this->redirectToRoute('projet_index');
        }

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pieceJointeFile = $form->get('pieceJointe')->getData();
            if ($pieceJointeFile) {
                if ($tache->getPieceJointeName()) {
                    $oldFilePath = $this->getParameter('uploads_directory').'/'.$tache->getPieceJointeName();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $originalFilename = pathinfo($pieceJointeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', null, $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pieceJointeFile->guessExtension();
                $pieceJointeFile->move($this->getParameter('uploads_directory'), $newFilename);
                $tache->setPieceJointeName($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'La tâche a été modifiée avec succès.');

            return $this->redirectToRoute('projet_show', ['id' => $tache->getProjet()->getId()]);
        }

        return $this->render('tache/edit.html.twig', [
            'tache' => $tache,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/taches/{id}/supprimer', name: 'tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$tache->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('projet_index');
        }

        if ($tache->getProjet()->getCreateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
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
