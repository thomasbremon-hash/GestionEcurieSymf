<?php

namespace App\Form;

use App\Entity\Cheval;
use App\Entity\Structure;
use App\Entity\Entreprise;
use App\Entity\Deplacement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DeplacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // NOM DU DÉPLACEMENT
            ->add('nom', TextType::class, [
                'label' => 'Nom du déplacement',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le nom du déplacement'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // DATE DU DÉPLACEMENT
            ->add('date', DateType::class, [
                'label' => 'Date du déplacement',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire'])
                ]
            ])



            ->add('chevaux', EntityType::class, [
                'class' => Cheval::class,
                'choice_label' => 'nom',
                'label' => 'Chevaux',
                'attr' => [
                    'class' => 'choice-multiple is-fullwidth'
                ],
                'multiple' => true,
                'expanded' => true,
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Veuillez sélectionner au moins un cheval'
                    ])
                ]
            ])

            // STRUCTURE
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'nom', // adapte selon ton entity
                'label' => 'Structure',
                'placeholder' => 'Sélectionner une structure',
                'attr' => [
                    'class' => 'select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La structure est obligatoire'])
                ]
            ])

            // ENTREPRISE
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom', // adapte selon ton entity
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionner une entreprise',
                'attr' => [
                    'class' => 'select'
                ],
                'constraints' => [
                    new NotBlank(['message' => "L'entreprise est obligatoire"])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Deplacement::class,
        ]);
    }
}
