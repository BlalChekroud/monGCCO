<?php

namespace App\Form;

use App\Repository\CityRepository;
use App\Entity\City;
use App\Repository\SiteCollectionRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\SiteCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteCollectionType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteName',TextType::class, [
                'label' => $this->translator->trans('site_collection.siteName').'<span class="requiredField">*</span>',
                'label_html' => true,
            ])
            ->add('siteCode',TextType::class, [
                'label' => $this->translator->trans('site_collection.siteCode').'<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('nationalSiteCode',TextType::class, [
                'label' => $this->translator->trans('site_collection.nationalSiteCode') .'<h6 class="explanation">' . $this->translator->trans('site_collection.siteCodeDescription') . '</h6>',
                'required' => false,
                'label_html' => true
            ])
            ->add('internationalSiteCode',TextType::class, [
                'label' => $this->translator->trans('site_collection.internationalSiteCode'),
                'required' => false,
            ])
            ->add('latDepart',TextType::class, [
                'label' => $this->translator->trans('site_collection.latDepart').'<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('longDepart',TextType::class, [
                'label' => $this->translator->trans('site_collection.longDepart').'<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('latFin',TextType::class, [
                'label' => $this->translator->trans('site_collection.latFin').'<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('longFin',TextType::class, [
                'label' => $this->translator->trans('site_collection.longFin').'<span class="requiredField">*</span>',
                'label_html' => true
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function (City $city) {
                    return $city->getName() . ' - ' . $city->getRegion()->getName() . ' (' . $city->getRegion()->getRegionCode() . ')';
                },
                'label' => $this->translator->trans('city.name').'<span class="requiredField">*</span>',
                'label_html' => true,
                'autocomplete' => true,
                'placeholder' => $this->translator->trans('city.choose'),
                'required' => true,
                'query_builder' => function (CityRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC'); // Or any other field you want to sort by
                },
                'attr' => ['class' => 'form-control']
            ])
            ->add('parentSite', EntityType::class, [
                'class' => SiteCollection::class, // Assurez-vous que l'entité est correcte
                'choice_label' => 'siteName', // Le champ à afficher dans la liste
                'label' => $this->translator->trans('site_collection.parentSite').'<h6 class="explanation">'.$this->translator->trans('site_collection.parentSiteDescription').'</h6>',
                'label_html' => true,
                'required' => false,
                'autocomplete' => true,
                'placeholder' => $this->translator->trans('site_collection.chooseParentSite'),
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
