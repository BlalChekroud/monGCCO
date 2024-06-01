<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
// use Symfony\Component\Form\Extension\Core\Type\TextType;

use App\Entity\BirdFamily;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BirdFamilyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('familyName')
            ->add('subFamily')
            ->add('tribe')
            ->add('save', SubmitType::class, [
                'label' => ' Enregistrer',
                'attr' => ['class' => 'btn btn-outline-primary bi bi-save'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdFamily::class,
        ]);
    }
}
