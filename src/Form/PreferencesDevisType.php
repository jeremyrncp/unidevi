<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferencesDevisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tvaRate', ChoiceType::class, [
                "label" => "TVA par défaut",
                "choices" => [
                    "0 %" => 0,
                    "5.5 %" => 5.5,
                    "10 %" => 10,
                    "20 %" => 20
                ]]
            )
            ->add('validityDevis', NumberType::class, ["label" => "Validité (jours)"])
            ->add('priceTypeDevis', ChoiceType::class, [
                "label" => "Affichage du prix",
                "choices" => [
                    "Forfait global" => User::PRICE_TYPE_DEVIS_GLOBAL,
                    "Prix par ligne" => User::PRICE_TYPE_DEVIS_PER_LINE
                ]
            ])
            ->add('mentionsLegalesDevis', TextareaType::class, ["label" => "Mentions légales"])
            ->add('displayFourchetteIA', CheckboxType::class, ["label" => "Afficher la fourchette IA avant validation du prix", "required" => false])
            ->add('proposerAutomatiquementUpsellsDevis', CheckboxType::class, ["label" => "Proposer automatiquement des upsells", "required" => false])
            ->add('autoriseSuppressionGlobaleUpsellsDevis', CheckboxType::class, ["label" => "Autoriser la suppression globale des upsells", "required" => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
