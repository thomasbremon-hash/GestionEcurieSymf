<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles', ChoiceType::class, [

                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                    'Client' => 'ROLE_CLIENT',
                    'Comptabilité' => 'ROLE_COMPTABILITE',
                ],
                'multiple' => true,
                'expanded' => true, // des cases à cocher
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmez le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                        'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une minuscule, un chiffre, et un caractère spécial.',
                    ]),
                ],
            ])

            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom'
                    ])
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom'
                    ])
                ]
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre rue'
                    ])
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre ville'
                    ])
                ]
            ])
            ->add('cp', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre code postal'
                    ])
                ]
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre pays'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
