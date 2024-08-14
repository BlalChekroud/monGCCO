<?php

namespace App\Form;

use App\Repository\CityRepository;
use App\Entity\City;
use App\Repository\SiteCollectionRepository;
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
                'label' => 'Nom du site<span class="requiredField">*</span>',
                'label_html' => true,
            ])
            ->add('siteCode',TextType::class, [
                'label' => 'Code du site<span class="requiredField">*</span>',
                'label_html' => true
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
                'label' => 'Latitude de départ<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('longDepart',TextType::class, [
                'label' => 'Longitude de départ<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('latFin',TextType::class, [
                'label' => 'Latitude de fin<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('longFin',TextType::class, [
                'label' => 'Longitude de fin<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city) {
                    return $city->getName() . ' - ' . $city->getCountry()->getName() . ' (' . $city->getCountry()->getIso2() . ')';
                },
                'label' => 'Ville<span class="requiredField">*</span>',
                'label_html' => true,
                'autocomplete' => true,
                'placeholder' => '-- Choisir une ville --',
                'required' => true,
                'query_builder' => function (CityRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC'); // Or any other field you want to sort by
                },
                'attr' => ['class' => 'form-control']
            ])
            // ->add('parentSiteName',TextType::class, [
            //     'label' => 'Nom du site parent<p>Si le site est un sous-site d\'un grand site ou une zone, ce vaste site est le site parent</p>',
            //     'label_html' => true,
            //     'required' => false,
            // ])
            ->add('parentSite', EntityType::class, [
                'class' => SiteCollection::class, // Assurez-vous que l'entité est correcte
                'choice_label' => 'siteName', // Le champ à afficher dans la liste
                'label' => 'Nom du site parent<p class="explanation">Si le site est un sous-site d\'un grand site ou une zone, ce vaste site est le site parent</p>',
                'label_html' => true,
                'required' => false,
                'autocomplete' => true,
                'placeholder' => '-- Choisir un site parent --',
                'query_builder' => function (SiteCollectionRepository $repository) {
                    return $repository->createQueryBuilder('s')
                        ->orderBy('s.siteName', 'ASC'); // Trier par nom du site
                },
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteCollection::class,
        ]);
    }
}
