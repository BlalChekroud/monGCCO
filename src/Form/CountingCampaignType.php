<?php

namespace App\Form;

use App\Entity\AgentsGroup;
use App\Repository\AgentsGroupRepository;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Repository\SiteCollectionRepository;
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
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de début',
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de fin'
            ])
            ->add('agentsGroups', EntityType::class, [
                'class' => AgentsGroup::class,
                'label' => 'Groupe(s):',
                'required' => true,
                'choice_label' => function (AgentsGroup $agentsGroup) {
                    return $agentsGroup->getGroupName() . ' / ' . $agentsGroup->getLeader()->getName() . ' ' . $agentsGroup->getLeader()->getLastName();
                },
                'expanded' => true,
                'multiple' => true,
                'query_builder' => function (AgentsGroupRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.groupName', 'ASC'); // Or any other field you want to sort by
                },
            ])
            ->add('siteCollection', EntityType::class, [
                'class' => SiteCollection::class,
                'label' => 'Sites de collection',
                'required' => true,
                'choice_label' => function (SiteCollection $siteCollection) {
                    return $siteCollection->getSiteName() . ' (' . $siteCollection->getCity()->getName() . ' / ' . $siteCollection->getCity()->getCountry()->getName() . ' )';
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
