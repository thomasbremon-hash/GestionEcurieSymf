<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => ['class' => 'input', 'placeholder' => 'Entrez le nom de l\'entreprise'],
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'attr' => ['class' => 'input', 'placeholder' => 'Entrez la rue'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'input', 'placeholder' => 'Entrez la ville'],
            ])
            ->add('cp', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'input', 'placeholder' => 'Entrez le code postal'],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => ['class' => 'input', 'placeholder' => 'Entrez le pays'],
            ])
            ->add('siren', TextType::class, [
                'label' => 'SIREN',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'SIREN de l\'entreprise'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'SIRET de l\'entreprise'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'Numéro de téléphone'],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'Email de l\'entreprise'],
            ])
            ->add('codeAPE', TextType::class, [
                'label' => 'Code APE',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'Ex: 0143Z'],
            ])
            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'FR76...'],
            ])
            ->add('bic', TextType::class, [
                'label' => 'BIC',
                'required' => false,
                'attr' => ['class' => 'input', 'placeholder' => 'Ex: CMCIFRPP'],
            ])
            // ->add('users', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => function (User $user) {
            //         return $user->getPrenom() . ' ' . $user->getNom();
            //     },
            //     'multiple' => true,
            //     'expanded' => true, // cases à cocher
            //     'label' => 'Utilisateurs affiliés',
            //     'required' => false,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
