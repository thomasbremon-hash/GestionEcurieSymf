<?php

namespace App\Form;

use App\Entity\Cheval;
use App\Entity\Course;
use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Cheval
            ->add('cheval', EntityType::class, [
                'class' => Cheval::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner le cheval',
                'attr' => [
                    'class' => 'select',
                ],
                'label' => 'Cheval',
            ])

            // Course
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => function (Course $course) {
                    return $course->getNom() . ' / ' . $course->getLieu();
                },
                'placeholder' => 'Sélectionner la course',
                'attr' => [
                    'class' => 'select',
                ],
                'label' => 'Course',
            ])

            // Position
            ->add('position', IntegerType::class, [
                'label' => 'Position (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la position du cheval',
                ],
            ])

            // Temps
            ->add('temps', TimeType::class, [
                'label' => 'Temps (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le temps',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}
