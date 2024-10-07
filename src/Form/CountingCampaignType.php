<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\CountingCampaign;
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
                'required' => true
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de fin',
                'required' => true
            ])
            ->add('siteAgentsGroups', CollectionType::class, [
                'entry_type' => SiteAgentsGroupType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,   // Permet l'ajout dynamique
                'allow_delete' => true, // Permet la suppression dynamique
                'by_reference' => false,
                'label' => false,
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
