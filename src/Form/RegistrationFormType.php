<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Cheval;
use App\Entity\Entreprise;
use App\Repository\ChevalRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre email',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre email'])
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                    'Client' => 'ROLE_CLIENT',
                    'Comptabilité' => 'ROLE_COMPTABILITE',
                ],
                'multiple' => true,
                'expanded' => true, // cases à cocher

            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre prénom',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom'])
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre nom',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom'])
                ]
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre rue',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre rue'])
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre ville',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre ville'])
                ]
            ])
            ->add('cp', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre code postal',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre code postal'])
                ]
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre pays',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre pays'])
                ]
            ])
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => function (Entreprise $entreprise) {
                    return $entreprise->getNom();
                },
                'multiple' => true,
                'expanded' => true,
                'label' => 'Entreprises',
                'required' => false,
            ])
            ->add('chevals', EntityType::class, [
                'class' => Cheval::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);



        if (!$isEdit) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Entrez votre mot de passe',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/i',
                        'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une minuscule, un chiffre et un caractère spécial.'
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false, // option personnalisée
        ]);
    }
}
