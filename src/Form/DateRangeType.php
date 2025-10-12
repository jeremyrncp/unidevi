<?php

namespace App\Form;

use App\VO\DateRangeVO;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateTimeType::class, ["label" => "Début"])
            ->add('end', DateTimeType::class, ["label" => "Fin"])
            ->add("submit", SubmitType::class, ["label" => "Visualiser", "attr" => ["class" => "btn btn-primary"]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateRangeVO::class,
        ]);
    }
}
