<?php

namespace App\Form;

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
            ->add('countType', ChoiceType::class, [
                'choices' => [
                    'U = Inconnu' => 'U',
                    'W = IWC (janvier)' => 'W',
                    'G = Oie' => 'G',
                    'X = Extra' => 'X',
                    'S = AfWC comptage juillet' => 'S',
                ],
                'label' => 'Type de comptage effectué lors de cette visite:',
            ])
            ->add('quality', ChoiceType::class, [
                'choices' => [
                    '-3 Inconnus' => -3,
                    "0 Compte 'réel'" => 0,
                    '-1 Estimation approximative' => -1,
                    '-2 Valeur extrapolée' => -2,
                ],
                'label' => 'Qualité:',
            ])
            ->add('totalCount')
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'A = Levé aérien' => 'A',
                    'B = Levé par bateau' => 'B',
                    'F = Levé à pieds ou voiture' => 'F',
                    'T = Télescope utilisé' => 'T',
                ],
                'label' => 'Méthode(s) utilisées pour le comptage:',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('disturbed', ChoiceType::class, [
                'choices' => [
                    'U = Inconnus' => 'U',
                    "N = Pas d'effet" => 'N',
                    "L = Peu d'effet" => 'L',
                    'M = Effet modéré' => 'M',
                    'S = Effet fort' => 'S',
                ],
                'label' => 'Disturbé(e) (Indique si le comptage a été affecté par une perturbation):',
            ])
            ->add('weather', ChoiceType::class, [
                'choices' => [
                    'U = Inconnus' => 'U',
                    "N = Pas d'effet" => 'N',
                    "L = Peu d'effet" => 'L',
                    'M = Effet modéré' => 'M',
                    'S = Effet fort' => 'S',
                ],
                'label' => 'Effets des conditions météorologiques (vent, pluie, brouillard) sur les comptages:',
            ])
            ->add('water', ChoiceType::class, [
                'choices' => [
                    'U = Inconnus' => 'U',
                    'N = Normale (humide)' => 'N',
                    'D = Sec' => 'D',
                    'O = Inondée' => 'O',
                ],
                'label' => "L'état des eaux lors du comptage:",
            ])
            ->add('ice', ChoiceType::class, [
                'choices' => [
                    'U = Inconnus' => 'U',
                    'N = Non congelés' => 'N',
                    'P = En partie gelée (< 90%)' => 'P',
                    'C = Complètement gelé' => 'C',
                ],
                'label' => 'La couverture de glace lors du comptage:',
            ])
            ->add('tidal', ChoiceType::class, [
                'choices' => [
                    'U = Inconnus' => 'U',
                    'N = Pas de marée' => 'N',
                    'R = Marée montante' => 'R',
                    'H = Marée haute' => 'H',
                    'F = marée descendante' => 'F',
                    'L = Marée basse' => 'L',
                ],
                'label' => 'Marée cours (la plupart) du comptage:',
            ])
            ->add('countingCampaign', EntityType::class, [
                'class' => CountingCampaign::class,
                'choice_label' => 'campaignName',
            ])
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
