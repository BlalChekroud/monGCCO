<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Region;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la région<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
                ])
            ->add('regionCode',TextType::class, [
                'label' => 'Code de la région<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
                ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => '-- Choisir le pays --',
                'autocomplete' => true,
                'label' => 'Pays<span class="requiredField">*</span>',
                'label_html' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Region::class,
        ]);
    }
}
