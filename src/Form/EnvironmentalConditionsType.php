<?php

namespace App\Form;

use App\Entity\Disturbed;
use App\Entity\EnvironmentalConditions;
use App\Entity\Ice;
use App\Entity\SiteCollection;
use App\Entity\Tidal;
use App\Entity\Water;
use App\Entity\Weather;
use App\Repository\SiteCollectionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvironmentalConditionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disturbed', EntityType::class, [
                'class' => Disturbed::class,
                'choice_label' => 'label',
                'label' => 'Disturbé(e) (Indique si le comptage a été affecté par une perturbation):',
            ])
            ->add('ice', EntityType::class, [
                'class' => Ice::class,
                'choice_label' => 'label',
                'label' => 'La couverture de glace lors du comptage:',
            ])
            ->add('tidal', EntityType::class, [
                'class' => Tidal::class,
                'choice_label' => 'label',
                'label' => 'Marée cours (la plupart) du comptage:',
            ])
            ->add('water', EntityType::class, [
                'class' => Water::class,
                'choice_label' => 'label',
                'label' => "L'état des eaux lors du comptage:",
            ])
            ->add('weather', EntityType::class, [
                'class' => Weather::class,
                'choice_label' => 'label',
                'label' => 'Effets des conditions météorologiques (vent, pluie, brouillard) sur les comptages:',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnvironmentalConditions::class,
        ]);
    }
}
