<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerDevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ["label" => "Nom complet ou nom société"])
            ->add('email', EmailType::class, ["label" => "Adresse email"])
            ->add('phone', TextType::class, ["label" => "Numéro de téléphone"])
            ->add('address', TextType::class, ["label" => "Adresse"])
            ->add('postalCode', TextType::class, ["label" => "Code postal"])
            ->add('city', TextType::class, ["label" => "Ville"])
            ->add('country', TextType::class, ["label" => "Pays"])
            ->add('siret', TextType::class, ["label" => "Numéro SIRET"])
            ->add('submit', SubmitType::class, ["label" => "Suivant", "attr" => ["class" => "btn btn-brand text-white"]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
