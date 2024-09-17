<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportCsvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('csvFile', FileType::class, [
            'label' => 'Importer un fichier CSV ou TXT',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'text/comma-separated-values',
                        'text/x-comma-separated-values',
                    ],
                    'mimeTypesMessage' => 'Veuillez charger un fichier CSV valide',
                    'maxSizeMessage' => 'Le fichier est trop volumineux. La taille maximale autorisée est 1024k.',
                ])
            ],
            'attr' => [
                    'accept' => '.csv,.txt'
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
