<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // if user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('projet_index');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // hash the password
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            $accountType = $form->get('accountType')->getData();
            $user->setRoles('chef' === $accountType ? ['ROLE_CHEF_PROJET'] : []);

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // add flash message
                $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('login');
            } catch (UniqueConstraintViolationException $e) {
                // Check which field caused the violation
                if (str_contains($e->getMessage(), 'pseudo')) {
                    $form->get('pseudo')->addError(new FormError('Ce pseudo est déjà utilisé.'));
                } elseif (str_contains($e->getMessage(), 'email')) {
                    $form->get('email')->addError(new FormError('Cette adresse email est déjà utilisée.'));
                } else {
                    $form->addError(new FormError('Une erreur est survenue lors de la création du compte.'));
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
