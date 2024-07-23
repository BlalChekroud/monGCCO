<?php

namespace App\Form;

use App\Entity\EnvironmentalConditions;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
// use App\Entity\SiteCollection;
use App\Entity\BirdSpecies;
use App\Entity\CollectedData;
use App\Entity\CountingCampaign;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectedDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('environmentalConditions', EntityType::class, [
            //     'class' => EnvironmentalConditions::class,
            //     'choice_label' => function (EnvironmentalConditions $envCond) {
            //         return $envCond->getSiteCollection()->getSiteName() .'/' . $envCond->getSiteCollection()->getCity()->getName() .' (' . $envCond->getCreatedAt()->format('d-m-Y') .')';
            //     },
            //     'label' => "Conditions d'environnement",
            //     'placeholder' => '--Choisir les conditions d\'environnement--',
            // ])
            ->add('countNumber')
            ->add('totalCount')
            // ->add('countType', ChoiceType::class, [
            //     'choices' => [
            //         'U = Inconnu' => 'U',
            //         'W = IWC (janvier)' => 'W',
            //         'G = Oie' => 'G',
            //         'X = Extra' => 'X',
            //         'S = AfWC comptage juillet' => 'S',
            //     ],
            //     'label' => 'Type de comptage effectué lors de cette visite:',
            // ])
            // ->add('quality', ChoiceType::class, [
            //     'choices' => [
            //         '-3 Inconnus' => -3,
            //         "0 Compte 'réel'" => 0,
            //         '-1 Estimation approximative' => -1,
            //         '-2 Valeur extrapolée' => -2,
            //     ],
            //     'label' => 'Qualité:',
            // ])

            // ->add('method', ChoiceType::class, [
            //     'choices' => [
            //         'A = Levé aérien' => 'A',
            //         'B = Levé par bateau' => 'B',
            //         'F = Levé à pieds ou voiture' => 'F',
            //         'T = Télescope utilisé' => 'T',
            //     ],
            //     'label' => 'Méthode(s) utilisées pour le comptage:',
            //     'expanded' => true,
            //     'multiple' => true,

            // ->add('siteCollection', EntityType::class, [
            //     'class' => SiteCollection::class,
            //     'choice_label' => function (SiteCollection $siteCollection) {
            //         return $siteCollection->getSiteName() . ' (' . $siteCollection->getRegion() . ')';
            //     },
            // ])
            // ->add('siteCollection', EntityType::class, [
            //     'class' => SiteCollection::class,
            //     'choice_label' => 'siteName',
            //     'label' => "Collection de sites",
            //     'placeholder' => '',
            // ])
            ->add('countingCampaign', EntityType::class, [
                'class' => CountingCampaign::class,
                'choice_label' => 'campaignName',
            ])
            ->add('birdSpecies', EntityType::class, [
                'class' => BirdSpecies::class,
                'choice_label' => 'scientificName',
                'multiple' => true,
                'expanded' => true,
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
