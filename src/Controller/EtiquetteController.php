<?php

namespace App\Controller;

use App\Entity\Etiquette;
use App\Form\EtiquetteType;
use App\Repository\EtiquetteRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etiquettes')]
#[IsGranted('ROLE_ADMIN')]
class EtiquetteController extends AbstractController
{
    #[Route(name: 'etiquette_index')]
    public function index(EtiquetteRepository $repository): Response
    {
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
            } catch (UniqueConstraintViolationException) {
                $form->get('nom')->addError(new FormError('Une étiquette avec ce nom existe déjà.'));
            }
        }

        return $this->render('etiquette/new.html.twig', [
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
