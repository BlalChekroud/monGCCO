<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Repository\SiteCollectionRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use App\Entity\CampaignStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountingCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('campaignName',TextType::class, [
            //     'label' => 'Nom de la campagne'
            // ])
            ->add('startDate', null, [
                'widget' => 'single_text',
                'label' => 'Date de début'
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
                'label' => 'Date de fin'
            ])
            ->add('siteCollection', EntityType::class, [
                'class' => SiteCollection::class,
                'label' => 'Sites de collection',
                'choice_label' => function (SiteCollection $siteCollection) {
                    return $siteCollection->getSiteName() . ' (' . $siteCollection->getRegion() . ')';
                },
                'expanded' => true,
                'multiple' => true,
                'query_builder' => function (SiteCollectionRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.siteName', 'ASC'); // Or any other field you want to sort by
                },
            ])
            ->add('campaignStatus', EntityType::class, [
                'class' => CampaignStatus::class,
                'choice_label' => 'label',
                'placeholder' => '',
                'required' => true,
            ])
            ->add('description',TextareaType::class, [
                'label' => 'Description',
                'required' => false,
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
