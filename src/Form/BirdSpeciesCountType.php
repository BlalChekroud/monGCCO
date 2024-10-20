<?php

namespace App\Form;

use App\Entity\BirdSpecies;
use App\Entity\BirdSpeciesCount;
use App\Entity\CollectedData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BirdSpeciesCountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('birdSpecies', EntityType::class, [
                'class' => BirdSpecies::class,
                'choice_label' => function(BirdSpecies $birdSpecy) {
                    return $birdSpecy->getScientificName() . ' - '. $birdSpecy->getBirdFamily()->getFamily() . ' (' . $birdSpecy->getBirdFamily()->getFamilyName() . ') '. $birdSpecy->getBirdFamily()->getSubFamily(). '/'. $birdSpecy->getBirdFamily()->getTribe().'/'. $birdSpecy->getBirdFamily()->getOrdre();
                },
                // 'label' => 'Espèce',
                'autocomplete' => true,
                'placeholder' => 'Nom scientifique - Famille (Nom de famille) / Sous-famille / Tribe / Ordre d\'espèce',
                'label' => false,

            ])
            ->add('count', IntegerType::class, [
                'label' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
            // ->add('count')
            // ->add('collectedData', EntityType::class, [
            //     'class' => CollectedData::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('birdSpecies', EntityType::class, [
            //     'class' => BirdSpecies::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdSpeciesCount::class,
        ]);
    }
}
