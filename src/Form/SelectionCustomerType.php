<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer;
use App\VO\SelectionCustomerVO;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectionCustomerType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("customer", EntityType::class, [
                'class' => Customer::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere("c.owner = :user")
                        ->setParameter("user", $this->security->getUser())
                        ->orderBy('c.name', 'ASC');
                },
                'choice_label' => 'nameUniq',
                "label" => "Client"
            ])
            ->add("submit",
                SubmitType::class,
                ["label" => "Suivant", "attr" => ["class" => "btn btn-primary"]]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SelectionCustomerVO::class,
        ]);
    }
}

