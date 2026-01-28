<?php

namespace App\Form;

use App\Entity\ChevalProprietaire;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChevalProprietaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ChevalProprietaireType.php
            ->add('proprietaire', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getPrenom() . ' ' . $u->getNom(),
                'placeholder' => 'Sélectionner un propriétaire',
                'attr' => ['class' => 'select'],
                'label' => 'Propriétaire',
                // PAS de multiple ici
            ])
            ->add('pourcentage', IntegerType::class, [
                'label' => 'Pourcentage',
                'required' => true,
                'attr' => [
                    'class' => 'input',
                    'min' => 0,
                    'max' => 100,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le pourcentage est obligatoire']),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ChevalProprietaire::class,
        ]);
    }
}