<?php

namespace App\Form;

use App\Entity\NatureReserve;
use App\Entity\SiteCollection;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NatureReserveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reserveName', TextType::class, [
                'label' => 'Nom de la réserve naturelle<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('reserveLeader', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Responsable de la réserve<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
                'placeholder' => '-- Choisir un responsable de la réserve --',
                'autocomplete' => true,
            ])
            ->add('siteCollections', EntityType::class, [
                'class' => SiteCollection::class,
                'choice_label' => function (SiteCollection $siteCollection) {
                    return $siteCollection->getSiteName() . ' / ' . $siteCollection->getCity()->getName() . ' (' . $siteCollection->getCity()->getRegion()->getRegionCode() . ')';
                },
                'label' => 'Les site de la réserve<span class="requiredField">*</span>',
                'label_html' => true,
                'multiple' => true,
                'required' => true,
                'placeholder' => '-- Choisir le(s) site(s) de la réserve --',
                'autocomplete' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NatureReserve::class,
        ]);
    }
}
