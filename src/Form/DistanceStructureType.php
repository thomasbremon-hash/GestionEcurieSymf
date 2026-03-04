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

            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom',
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionner une entreprise',
                'constraints' => [
                    new NotBlank()
                ]
            ])

            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'nom',
                'label' => 'Structure',
                'placeholder' => 'Sélectionner une structure',
                'constraints' => [
                    new NotBlank()
                ]
            ])

            ->add('distance', IntegerType::class, [
                'label' => 'Distance (km)',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Ex : 52'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La distance est obligatoire'
                    ]),
                    new Positive([
                        'message' => 'La distance doit être positive'
                    ])
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
