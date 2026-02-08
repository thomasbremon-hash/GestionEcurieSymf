<?php

// src/Form/FacturationGenerationType.php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\MoisDeGestion;
use Dom\Attr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class FacturationGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'attr' => [
                    'class' => 'select',
                ],
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une entreprise facturante',
                'required' => true,
            ])
            ->add('moisDeGestion', EntityType::class, [
                'class' => MoisDeGestion::class,
                'attr' => [
                    'class' => 'select',
                ],
                'choice_label' => fn(MoisDeGestion $m) =>
                sprintf('%02d / %d', $m->getMois(), $m->getAnnee()),
                'placeholder' => 'Choisir un mois',
                'required' => true,
            ]);
    }
}
