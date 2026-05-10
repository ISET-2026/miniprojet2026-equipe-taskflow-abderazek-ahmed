<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre@email.com'],
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => ['placeholder' => 'Votre pseudo'],
            ])
            ->add('accountType', ChoiceType::class, [
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Utilisateur normal' => 'user',
                    'Chef de projet' => 'chef',
                ],
                'data' => 'user',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un type de compte.'),
                ],
                'choice_attr' => static fn () => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Mot de passe',
                'mapped' => false, // Important: not mapped to User entity
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['placeholder' => 'Mot de passe'],
                    'constraints' => [
                        new NotBlank(message: 'Veuillez entrer un mot de passe'),
                        new Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['placeholder' => 'Confirmer le mot de passe'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'register',
        ]);
    }
}
