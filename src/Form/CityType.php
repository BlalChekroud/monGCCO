<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Region;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la ville<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'name',
                'placeholder' => '-- Choisir la région --',
                'autocomplete' => true,
                'label' => 'Région<span class="requiredField">*</span>',
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
