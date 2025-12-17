<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // NOM
            ->add('nom', TextType::class, [
                'label' => 'Nom de la course',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le nom de la course'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // LIEU
            ->add('lieu', TextType::class, [
                'label' => 'Lieu / Hippodrome',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le lieu de la course'
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le lieu ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // DATE DE LA COURSE
            ->add('dateCourse', DateType::class, [
                'label' => 'Date de la course',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'input',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                ]
            ])

            // DISTANCE
            ->add('distance', IntegerType::class, [
                'label' => 'Distance (mètres)',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la distance en mètres'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La distance est obligatoire']),
                    new Positive(['message' => 'La distance doit être un nombre positif'])
                ]
            ])

            // TYPE DE COURSE
            ->add('type', ChoiceType::class, [
                'label' => 'Type de course',
                'choices' => [
                    'Galop' => 'Galop',
                    'Trot' => 'Trot',
                    'Obstacle' => 'Obstacle'
                ],
                'placeholder' => 'Sélectionner un type',
                'attr' => [
                    'class' => 'select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type est obligatoire']),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
