<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportCsvType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('csvFile', FileType::class, [
            // 'label' => 'Importer un fichier CSV ou TXT',
            'label' => false,
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
                    'mimeTypesMessage' => $this->translator->trans('import_csv.mimeTypesMessage'),
                    'maxSizeMessage' => $this->translator->trans('maxSizeMessage'),
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
