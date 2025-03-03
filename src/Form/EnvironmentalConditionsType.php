<?php

namespace App\Form;

use App\Entity\Disturbed;
use App\Entity\EnvironmentalConditions;
use App\Entity\Ice;
use App\Entity\Tidal;
use App\Entity\Water;
use App\Entity\Weather;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnvironmentalConditionsType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disturbed', EntityType::class, [
                'class' => Disturbed::class,
                'choice_label' => 'label',
                'placeholder' => $this->translator->trans('conditionDisturbed.choose'),
                'autocomplete' => true,
                'label' => $this->translator->trans('conditionDisturbed.description'),
                'label_html' => true,
            ])
            ->add('ice', EntityType::class, [
                'class' => Ice::class,
                'choice_label' => 'label',
                'placeholder' => $this->translator->trans('conditionIce.choose'),
                'autocomplete' => true,
                'label' => $this->translator->trans('conditionIce.description'),
                'label_html' => true,
            ])
            ->add('tidal', EntityType::class, [
                'class' => Tidal::class,
                'choice_label' => 'label',
                'placeholder' => $this->translator->trans('conditionTidal.choose'),
                'autocomplete' => true,
                'label' => $this->translator->trans('conditionTidal.description'),
                'label_html' => true,
            ])
            ->add('water', EntityType::class, [
                'class' => Water::class,
                'choice_label' => 'label',
                'placeholder' => $this->translator->trans('conditionWater.choose'),
                'autocomplete' => true,
                'label' => $this->translator->trans('conditionWater.description'),
                'label_html' => true,
            ])
            ->add('weather', EntityType::class, [
                'class' => Weather::class,
                'choice_label' => 'label',
                'placeholder' => $this->translator->trans('conditionWeather.choose'),
                'autocomplete' => true,
                'label' => $this->translator->trans('conditionWeather.description'),
                'label_html' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnvironmentalConditions::class,
        ]);
    }
}
