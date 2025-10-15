<?php

namespace App\Controller\Admin;

use App\Entity\Logger;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LoggerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Logger::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new("createdAt"),
            TextField::new('type'),
            TextEditorField::new('prompt'),
            TextEditorField::new('text'),
            TextEditorField::new('result')
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Logger')
            ->setEntityLabelInPlural("Logger")
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }
}
