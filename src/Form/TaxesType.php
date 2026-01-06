<?php

namespace App\Form;

use App\Entity\Taxes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // LIBELLÉ
            ->add('libelle', TextType::class, [
                'label' => 'Libellé de la taxe',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Ex : TVA, Taxe régionale'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le libellé est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le libellé ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])

            // POURCENTAGE
            ->add('pourcentage', NumberType::class, [
                'label' => 'Pourcentage (%)',
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Ex : 20',
                    'step' => '0.01',
                    'min' => 0,
                    'max' => 100
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le pourcentage est obligatoire']),
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Le pourcentage doit être compris entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Taxes::class,
        ]);
    }
}
