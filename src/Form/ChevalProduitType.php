<?php

namespace App\Form;

use App\Entity\Cheval;
use App\Entity\Produit;
use App\Entity\ChevalProduit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ChevalProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'class' => 'input',
                    'placeholder' => 'Entrez la quantité'
                ],
                'constraints' => [new NotBlank()],
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChevalProduit::class,
        ]);
    }
}