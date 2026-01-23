<?php

namespace App\Form;

use App\Entity\MoisDeGestion;
use App\Form\ChevalProduitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Entreprise;

class MoisDeGestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mois', IntegerType::class, [
                'attr' => ['min' => 1, 'max' => 12, 'class' => 'input'],
                'constraints' => [new NotBlank()],
            ])
            ->add('annee', IntegerType::class, [
                'attr' => ['class' => 'input'],
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