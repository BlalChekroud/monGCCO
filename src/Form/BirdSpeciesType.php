<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


use App\Entity\BirdFamily;
use App\Entity\BirdSpecies;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BirdSpeciesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scientificName')
            ->add('frenchName',TextType::class, [
                'label' => 'Nom français'
            ])
            ->add('wispeciescode')
            ->add('imageFile', FileType::class, [
                'required' => false,
                'label' => 'Image',
                'mapped' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpg', 'image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Svp charger une image validé',
                    ])
                ],
            ])
            // ->add('imageFile', VichImageType::class, [
            //     'required' => false,
            //     'label' => "Image d'oiseau"
            // ])
            ->add('authority')
            ->add('birdLifeTaxTreat')
            ->add('commonName')
            ->add('commonNameAlt')
            ->add('iucnRedListCategory')
            ->add('synonyms')
            ->add('taxonomicSources')
            ->add('sisRecId')
            ->add('spcRecId')
            ->add('subsppId')
            // ->add('createdAt', null, [
            //     'widget' => 'single_text',
            // ])
            // ->add('updatedAt', null, [
            //     'widget' => 'single_text',
            // ])
            ->add('birdFamily', EntityType::class, [
                'class' => BirdFamily::class,
                'choice_label' => 'familyname',
                'label' => "Famille d'espèce",
                'placeholder' => 'Inconnu',
            ])
            ->add('save', SubmitType::class, [
                'label' => ' Enregistrer',
                'attr' => ['class' => 'btn btn-outline-primary bi bi-save'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdSpecies::class,
        ]);
    }
}
