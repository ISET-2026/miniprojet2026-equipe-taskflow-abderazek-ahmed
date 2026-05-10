<?php

namespace App\Form;

use App\Entity\Etiquette;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class EtiquetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'étiquette',
                'attr' => ['placeholder' => 'Ex: Bug, Feature, Urgent'],
            ])
            ->add('couleur', TextType::class, [
                'label' => 'Couleur (code hex)',
                'attr' => [
                    'placeholder' => '#3498DB',
                    'class' => 'color-picker',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex(
                        pattern: '/^#[0-9A-Fa-f]{6}$/',
                        message: 'Le code couleur doit être au format hexadécimal (#RRGGBB)'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etiquette::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'etiquette',
        ]);
    }
}
