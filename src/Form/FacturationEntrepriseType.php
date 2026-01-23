<?php

namespace App\Form;

use App\Entity\FacturationEntreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturationEntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Total facture
            ->add('total', MoneyType::class, [
                'label' => 'Total (€)',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'input is-small',
                    'readonly' => true, // lecture seule car calculé automatiquement
                ],
            ])

            // Entreprise (masqué pour garder l'info)
            ->add('entreprise', HiddenType::class)

            // Mois de gestion (masqué)
            ->add('moisDeGestion', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacturationEntreprise::class,
        ]);
    }
}
