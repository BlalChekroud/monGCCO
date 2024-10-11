<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use App\Entity\Coverage;
use App\Entity\BirdLifeTaxTreat;
use App\Entity\IucnRedListCategory;
use App\Repository\BirdFamilyRepository;

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
                'label' => 'Nom français',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('birdFamily', EntityType::class, [
                'class' => BirdFamily::class,
                'autocomplete' => true,
                'choice_label' => function (BirdFamily $birdFamily) {
                    return $birdFamily->getFamily() . ' (' . $birdFamily->getFamilyName() . ') \ ' .$birdFamily->getSubFamily() . ' \ ' .$birdFamily->getTribe(). ' \ ' .$birdFamily->getOrdre();
                },
                'label' => 'Famille (Nom de famille) \ Sous-famille \ Tribe \ Ordre d\'espèce<span class="requiredField">*</span>',
                'label_html' => true,
                'placeholder' => 'Choisir une Famille d\'Oiseaux',
                'required' => true,
                'query_builder' => function (BirdFamilyRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.family', 'ASC');
                },
            ])
            ->add('wispeciescode',TextType::class, [
                'required' => false,
                'empty_data' => '',
            ])
            // ->add('imageFile', FileType::class, [
            //     'label' => 'Image (JPG ou PNG)',
            //     'mapped' => false,
            //     'required' => false,
            //     'constraints' => [
            //         new File([
            //             'maxSize' => '1024k',
            //             'mimeTypes' => [
            //                 'image/jpeg',
            //                 'image/png',
            //             ],
            //             'mimeTypesMessage' => "Veuillez télécharger un fichier d'image valide (JPEG ou PNG)",
            //         ])
            //     ],
            // ])
            ->add('image', ImageType::class, [
                'label' => 'Inserer une image<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false
            ])
            ->add('authority',TextType::class, [
                'required' => false,
                'empty_data' => '',
            ])
            ->add('coverage', EntityType::class, [
                'class' => Coverage::class,
                'choice_label' => 'label',
                'label' => 'Couverture pour cette espèce',
                // 'placeholder' => '',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('birdLifeTaxTreat', EntityType::class, [
                'class' => BirdLifeTaxTreat::class,
                'choice_label' => 'label',
                'label' => 'Traitement taxonomique de BirdLife',
                // 'placeholder' => '',
                'required' => false,
                'attr' => ['class' => 'form-control', 'id' => 'birdLifeTaxTreatDropdown']
            ])
            ->add('commonName')
            ->add('commonNameAlt')
            // ->add('iucnRedListCategory', ChoiceType::class, [
            //     'choices' => [
            //         '' => 'NULL',
            //         'CR' => 'CR',
            //         'CR (PE)' => 'CR (PE)',
            //         'DD' => 'DD',
            //         'EN' => 'EN',
            //         'EW' => 'EW',
            //         'EX' => 'EX',
            //         'LC' => 'LC',
            //         'NT' => 'NT',
            //         'VU' => 'VU',
            //     ],
            //     'label' => "Catégorie de la liste rouge de l'UICN 2022",
            // ])
            ->add('iucnRedListCategory', EntityType::class, [
                'class' => IucnRedListCategory::class,
                'choice_label' => 'label',
                'label' => "Catégorie de la liste rouge de l'UICN 2022",
                // 'placeholder' => '',
                'required' => false,
                'attr' => ['class' => 'form-control', 'id' => 'iucnRedListCategoryDropdown']
            ])
            ->add('synonyms')
            ->add('taxonomicSources')
            ->add('sisRecId')
            ->add('spcRecId')
            ->add('subsppId')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdSpecies::class,
        ]);
    }
}
