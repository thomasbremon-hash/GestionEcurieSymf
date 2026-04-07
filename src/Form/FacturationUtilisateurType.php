<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\MoisDeGestion;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FacturationUtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('utilisateur', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn(User $u) => $u->getNom() . ' ' . $u->getPrenom(),
                'placeholder'  => 'Choisir un client',
                'required'     => true,
            ])
            ->add('moisDeGestion', EntityType::class, [
                'class'        => MoisDeGestion::class,
                'choice_label' => fn(MoisDeGestion $m) => sprintf('%02d / %d', $m->getMois(), $m->getAnnee()),
                'placeholder'  => 'Choisir un mois',
                'required'     => true,
            ])
            ->add('entreprise', EntityType::class, [
                'class'        => Entreprise::class,
                'choice_label' => 'nom',
                'placeholder'  => 'Choisir une entreprise',
                'required'     => true,
            ]);
    }
}
