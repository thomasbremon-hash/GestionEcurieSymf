<?php

namespace App\Form;

use App\Entity\Structure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // NOM
            ->add('nom', TextType::class, [
                'label' => 'Nom de la structure',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le nom de la structure',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            // RUE
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la rue',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La rue est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La rue ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            // VILLE
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez la ville',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La ville est obligatoire']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            // CODE POSTAL
            ->add('cp', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez le code postal',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le code postal est obligatoire']),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'Le code postal ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Structure::class,
        ]);
    }
}
