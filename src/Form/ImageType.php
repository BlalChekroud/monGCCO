<?php

namespace App\Form;

use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints\File;
use App\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'label' => $this->translator->trans('image.insert'),
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'mapped' => false,
                'required' => true,
                // 'download_uri' => false,
                'delete_label' => $this->translator->trans('vich_uploader.form_label.delete_confirm'),
                'download_label' => $this->translator->trans('vich_uploader.link.download'),
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('image.mimeTypesMessage'),
                        'maxSizeMessage' => $this->translator->trans('maxSizeMessage'),
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
