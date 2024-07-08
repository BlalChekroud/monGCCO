<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountingCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campaignName',TextType::class, [
                'label' => 'Nom de la campagne'
            ])
            ->add('startDate', null, [
                'widget' => 'single_text',
                'label' => 'Date de début'
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
                'label' => 'Date de fin'
            ])
            // ->add('siteCollection', EntityType::class, [
            //     'class' => SiteCollection::class,
            //     'choice_label' => 'siteName','region',
            //     'expanded' => true,
            //     'multiple' => true,
            // ])
            ->add('siteCollection', EntityType::class, [
                'class' => SiteCollection::class,
                'choice_label' => function (SiteCollection $siteCollection) {
                    return $siteCollection->getSiteName() . ' (' . $siteCollection->getRegion() . ')';
                },
                'expanded' => true,
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CountingCampaign::class,
        ]);
    }
}
