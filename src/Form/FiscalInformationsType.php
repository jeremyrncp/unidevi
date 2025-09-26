<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FiscalInformationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, ["label" => "Nom de la société"])
            ->add('juridical', ChoiceType::class, [
                "label" => "Forme juridique",
                "choices" => [
                    "SARL" => "SARL",
                    "SAS" => "SAS",
                    "SASU" => "SASU",
                    "EI" => "EI",
                    "Auto entreprise" => "Auto entreprise"
                ]]
            )
            ->add('adresse', TextType::class, ["label" => "Adresse"])
            ->add('postalCode', TextType::class, ["label" => "Code postal"])
            ->add('country', TextType::class, ["label" => "Pays"])
            ->add('phoneNumber', TextType::class, ["label" => "Téléphone"])
            ->add('emailContact', EmailType::class, ["label" => "Email de contact"])
            ->add('siret', TextType::class, ["label" => "Numéro SIRET"])
            ->add('avisGoogle', TextType::class, ["label" => "URL de votre fiche Google Business Profile"])
            ->add('isDisplayAvisGoogle', CheckboxType::class, ["label" => "Afficher le badge Google sur mes devis", "required" => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
