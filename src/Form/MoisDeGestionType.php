<?php

namespace App\Form;

use App\Entity\MoisDeGestion;
use App\Form\ChevalProduitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Entreprise;

class MoisDeGestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mois', ChoiceType::class, [
                'choices' => [
                    'Janvier' => 1,
                    'Février' => 2,
                    'Mars' => 3,
                    'Avril' => 4,
                    'Mai' => 5,
                    'Juin' => 6,
                    'Juillet' => 7,
                    'Août' => 8,
                    'Septembre' => 9,
                    'Octobre' => 10,
                    'Novembre' => 11,
                    'Décembre' => 12,
                ],
                'placeholder' => 'Sélectionner un mois',

                'constraints' => [new NotBlank()],
            ])
            ->add('annee', ChoiceType::class, [
                'choices' => array_combine(
                    range(date('Y') - 2, date('Y') + 2),
                    range(date('Y') - 2, date('Y') + 2),
                ),
                'placeholder' => 'Sélectionner une année',

                'constraints' => [new NotBlank()],
            ])

            ->add('chevalProduits', CollectionType::class, [
                'entry_type' => ChevalProduitType::class,
                'allow_add' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MoisDeGestion::class,
        ]);
    }
}
