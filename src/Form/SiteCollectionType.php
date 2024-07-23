<?php

namespace App\Form;

use App\Repository\CityRepository;
use App\Entity\City;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\CountingCampaign;
use App\Entity\SiteCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteName',TextType::class, [
                'label' => 'Nom du site'
            ])
            ->add('siteCode',TextType::class, [
                'label' => 'Code du site'
            ])
            ->add('nationalSiteCode',TextType::class, [
                'label' => 'Code national',
                'required' => false,
            ])
            ->add('internationalSiteCode',TextType::class, [
                'label' => 'Code international',
                'required' => false,
            ])
            ->add('latDepart',TextType::class, [
                'label' => 'Latitude de départ'
            ])
            ->add('longDepart',TextType::class, [
                'label' => 'Longitude de départ'
            ])
            ->add('latFin',TextType::class, [
                'label' => 'Latitude de fin'
            ])
            ->add('longFin',TextType::class, [
                'label' => 'Longitude de fin'
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city) {
                    return $city->getName() . ' - ' . $city->getCountry()->getName() . ' (' . $city->getCountry()->getIso2() . ')';
                },
                'label' => 'Ville',
                'placeholder' => 'Choisir une ville',
                'required' => true,
                'query_builder' => function (CityRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC'); // Or any other field you want to sort by
                },
                'attr' => ['class' => 'form-control']
            ])
            ->add('parentSiteName',TextType::class, [
                'label' => 'Nom du site parent',
                'required' => false,
            ])
            // ->add('siteName')
            // ->add('siteCode')
            // ->add('nationalSiteCode')
            // ->add('internationalSiteCode')
            // ->add('latDepart')
            // ->add('longDepart')
            // ->add('latFin')
            // ->add('longFin')
            // ->add('region')
            // ->add('parentSiteName')


            // ->add('countingCampaigns', EntityType::class, [
            //     'class' => CountingCampaign::class,
            //     'choice_label' => 'id',
            //     'multiple' => true,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteCollection::class,
        ]);
    }
}
