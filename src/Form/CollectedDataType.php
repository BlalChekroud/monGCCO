<?php

namespace App\Form;

use App\Entity\CountType;
use App\Entity\Method;
use App\Entity\Quality;
use App\Entity\CollectedData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectedDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countType', EntityType::class, [
                'class' => CountType::class,
                'choice_label' => 'label',
                'label' => "Type de comptage effectué lors de cette visite:<span class='requiredField'>*</span>",
                'label_html' => true,
                'placeholder' => '-- Choisir un type --',
                'required' => true,
            ])
            ->add('quality', EntityType::class, [
                'class' => Quality::class,
                'choice_label' => 'label',
                'label' => 'Qualité:<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('method', EntityType::class, [
                'class' => Method::class,
                'choice_label' => 'label',
                'label' => 'Méthode(s) utilisées pour le comptage:',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            // ->add('birdSpecies', EntityType::class, [
            //     'class' => BirdSpecies::class,
            //     'choice_label' => function(BirdSpecies $birdSpecy) {
            //         return $birdSpecy->getScientificName() . ' ('. $birdSpecy->getBirdFamily()->getFamily() . ')' . $birdSpecy->getImageFile();
            //     },
            //     'label' => 'Espèce oiseau',
            //     'multiple' => true,
            //     'expanded' => true,
            // ])
            ->add('birdSpeciesCounts', CollectionType::class, [
                'entry_type' => BirdSpeciesCountType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                // 'label' => 'Oiseaux comptées :',
                'label' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CollectedData::class,
        ]);
    }
}
