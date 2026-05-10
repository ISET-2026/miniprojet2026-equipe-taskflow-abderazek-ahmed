<?php

namespace App\Form;

use App\Entity\Etiquette;
use App\Entity\Projet;
use App\Entity\Tache;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Projet|null $projet */
        $projet = $options['data']->getProjet() ?? $options['projet'] ?? null;

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre de la tâche'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Description optionnelle', 'rows' => 4],
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'basse',
                    'Moyenne' => 'moyenne',
                    'Haute' => 'haute',
                    'Urgente' => 'urgente',
                ],
            ])
            ->add('dateEcheance', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('assigneA', EntityType::class, [
                'label' => 'Assigner à',
                'class' => User::class,
                'choice_label' => 'pseudo',
                'required' => false,
                'placeholder' => 'Choisir un utilisateur',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('etiquettes', EntityType::class, [
                'label' => 'Étiquettes',
                'class' => Etiquette::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'required' => false,
            ])
            ->add('pieceJointe', FileType::class, [
                'label' => 'Pièce jointe (max 5 Mo)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'text/plain',
                        ],
                        mimeTypesMessage: 'Veuillez uploader un fichier valide (PDF, DOC, DOCX, images, texte)'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'tache',
            'projet' => null,
        ]);
    }
}
