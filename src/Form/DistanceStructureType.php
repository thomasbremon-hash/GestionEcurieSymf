<?php

namespace App\Form;

use App\Entity\DistanceStructure;
use App\Entity\Entreprise;
use App\Entity\Structure;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class DistanceStructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // DISTANCE
            ->add('distance', IntegerType::class, [
                'label' => 'Distance (km)',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la distance'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La distance est obligatoire']),
                    new Positive(['message' => 'La distance doit être positive'])
                ]
            ])

            // STRUCTURE
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'nom', // plus lisible que l'id
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
                'choice_label' => 'nom', // adapte si nécessaire
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionner une entreprise',
                'attr' => [
                    'class' => 'select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L’entreprise est obligatoire'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DistanceStructure::class,
        ]);
    }
}
