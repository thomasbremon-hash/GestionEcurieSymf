<?php

namespace App\Form;

use App\Entity\Produit;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FloatType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // NOM DU PRODUIT
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le nom du produit'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // PRIX
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le prix'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire']),
                    new Positive(['message' => 'Le prix doit être positif'])
                ]
            ])

            // DESCRIPTION
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'textarea',
                    'placeholder' => 'Décrivez le produit',
                    'rows' => 4
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            ->add('tauxTVA', NumberType::class, [
                'label' => 'Taux de TVA (%)',
                'scale' => 2,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le taux de TVA',
                    'step' => '0.01',
                    'min' => 0,
                    'max' => 100
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le taux de TVA est obligatoire']),
                    new Positive(['message' => 'Le taux de TVA doit être positif'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
