<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label' => 'Nom français'
            ])
            ->add('wispeciescode')
            ->add('imageFile', FileType::class, [
                'label' => 'Image (JPG ou PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => "Veuillez télécharger un fichier d'image valide (JPEG ou PNG)",
                    ])
                ],
            ])
            ->add('authority')
            // ->add('coverage', ChoiceType::class, [
            //     'choices' => [
            //         'U(inconnus)' => 'U',
            //         'B(mauvaise) = <25%' => 'B',
            //         'M(modérée)= 25-50%' => 'M',
            //         'G(bonne) = 51-75%' => 'G',
            //         'E(excellente) = 76-100%' => 'E',
            //     ],
            //     'label' => 'Couverture pour cette espèce',
            // ])
            // ->add('birdLifeTaxTreat', ChoiceType::class, [
            //     'choices' => [
            //         'R = Reconnu comme espèce' => 'R',
            //         'NR = Non Reconnu comme espèce' => 'NR',
            //     ],
            //     'label' => 'Traitement taxonomique de BirdLife',
            // ])
            ->add('coverage', EntityType::class, [
                'class' => Coverage::class,
                'choice_label' => 'label',
                'label' => 'Couverture pour cette espèce',
                'placeholder' => '',
                'attr' => ['class' => 'form-control']
            ])
            ->add('birdLifeTaxTreat', EntityType::class, [
                'class' => BirdLifeTaxTreat::class,
                'choice_label' => 'label',
                'label' => 'Traitement taxonomique de BirdLife',
                'placeholder' => '',
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
                'placeholder' => '',
                'attr' => ['class' => 'form-control', 'id' => 'iucnRedListCategoryDropdown']
            ])
            ->add('synonyms')
            ->add('taxonomicSources')
            ->add('sisRecId')
            ->add('spcRecId')
            ->add('subsppId')
            ->add('birdFamily', EntityType::class, [
                'class' => BirdFamily::class,
                'choice_label' => function (BirdFamily $birdFamily) {
                    return $birdFamily->getFamily() . ' (' . $birdFamily->getFamilyName() . ') \ ' .$birdFamily->getSubFamily() . ' \ ' .$birdFamily->getTribe();
                },
                'label' => "Famille (Nom de famille) \ Sous-famille \ Tribe d'espèce",
                'placeholder' => '',
                'required' => true,
                'query_builder' => function (BirdFamilyRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.family', 'ASC'); // Or any other field you want to sort by
                },
            ])
            // ->add('save', SubmitType::class, [
            //     'label' => 'Enregistrer les modifications'
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdSpecies::class,
        ]);
    }
}
