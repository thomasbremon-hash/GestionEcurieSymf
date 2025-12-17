<?php

namespace App\Form;

use App\Entity\Cheval;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChevalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // NOM
            ->add('nom', null, [
                'label' => 'Nom du cheval',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le nom du cheval'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // RACE
            ->add('race', null, [
                'label' => 'Race',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la race'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La race ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // SEXE
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Hongre' => 'Hongre',
                    'Jument' => 'Jument',
                    'Étalon' => 'Étalon',
                ],
                'placeholder' => 'Sélectionner un sexe',
                'attr' => [
                    'class' => 'select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le sexe est obligatoire']),
                ]
            ])

            // DATE DE NAISSANCE
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'input',
                ]
            ])

            // PROPRIÉTAIRE
            ->add('proprietaire', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'label' => 'Propriétaire',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cheval::class,
        ]);
    }
}
