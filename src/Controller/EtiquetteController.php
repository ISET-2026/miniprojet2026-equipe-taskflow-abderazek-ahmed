<?php

namespace App\Controller;

use App\Entity\Etiquette;
use App\Form\EtiquetteType;
use App\Repository\EtiquetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etiquettes')]
#[IsGranted('ROLE_ADMIN')]
class EtiquetteController extends AbstractController
{
    #[Route(name: 'etiquette_index', defaults: ['id' => null])]
    #[Route('/{id}', name: 'etiquette_show', requirements: ['id' => '\d+'])]
    public function index(EtiquetteRepository $repository, ?Etiquette $etiquette = null): Response
    {
        if ($etiquette) {
            return $this->render('etiquette/show.html.twig', [
                'etiquette' => $etiquette,
            ]);
        }

        return $this->render('etiquette/index.html.twig', [
            'etiquettes' => $repository->findAll(),
        ]);
    }

    #[Route('/nouvelle', name: 'etiquette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $etiquette = new Etiquette();
        $form = $this->createForm(EtiquetteType::class, $etiquette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($etiquette);
                $em->flush();

                $this->addFlash('success', 'L\'étiquette a été créée avec succès.');

                return $this->redirectToRoute('etiquette_index');
            } catch (UniqueConstraintViolationException $e) {
                $form->get('nom')->addError(new FormError('Une étiquette avec ce nom existe déjà.'));
            }
        }

        return $this->render('etiquette/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'etiquette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Etiquette $etiquette, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EtiquetteType::class, $etiquette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                $this->addFlash('success', 'L\'étiquette a été modifiée avec succès.');

                return $this->redirectToRoute('etiquette_index');
            } catch (UniqueConstraintViolationException $e) {
                $form->get('nom')->addError(new FormError('Une étiquette avec ce nom existe déjà.'));
            }
        }

        return $this->render('etiquette/edit.html.twig', [
            'etiquette' => $etiquette,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'etiquette_delete', methods: ['POST'])]
    public function delete(Request $request, Etiquette $etiquette, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$etiquette->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('etiquette_index');
        }

        $em->remove($etiquette);
        $em->flush();

        $this->addFlash('success', 'L\'étiquette a été supprimée avec succès.');

        return $this->redirectToRoute('etiquette_index');
    }
}
