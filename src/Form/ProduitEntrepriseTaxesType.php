<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Produit;
use App\Entity\ProduitEntrepriseTaxes;
use App\Entity\Taxes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProduitEntrepriseTaxesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // ENTREPRISE
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom', // adapte si nécessaire
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionnez une entreprise',
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une entreprise'
                    ])
                ]
            ])

            // PRODUIT
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom', // adapte si nécessaire
                'label' => 'Produit',
                'placeholder' => 'Sélectionnez un produit',
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un produit'
                    ])
                ]
            ])

            // TAXES
            ->add('taxes', EntityType::class, [
                'class' => Taxes::class,
                'choice_label' => function (Taxes $taxe) {
                    return $taxe->getPourcentage() . ' %';
                },
                'label' => 'Taxe',
                'placeholder' => 'Sélectionnez une taxe',
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une taxe'
                    ])
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProduitEntrepriseTaxes::class,
        ]);
    }
}
