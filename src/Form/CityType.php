<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Region;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CityType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('city.name').'<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('latitude', TextType::class, [
                'label' => $this->translator->trans('city.latitude').'<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('longitude', TextType::class, [
                'label' => $this->translator->trans('city.longitude').'<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'name',
                'placeholder' => $this->translator->trans('region.select_the_region'),
                'autocomplete' => true,
                'label' => $this->translator->trans('region.name').'<span class="requiredField">*</span>',
                'label_html' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }
}
