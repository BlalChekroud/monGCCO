<?php

namespace App\Form;

use App\Entity\CountType;
use App\Entity\Method;
use App\Entity\Quality;
use App\Entity\CollectedData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CollectedDataType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countType', EntityType::class, [
                'class' => CountType::class,
                'choice_label' => 'label',
                'label' => $this->translator->trans('countType.label') . "<span class='requiredField'>*</span>",
                'label_html' => true,
                'placeholder' => $this->translator->trans('countType.placeholder'),
                'required' => true,
            ])
            ->add('quality', EntityType::class, [
                'class' => Quality::class,
                'choice_label' => 'label',
                'label' => $this->translator->trans('quality') .' :<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('method', EntityType::class, [
                'class' => Method::class,
                'choice_label' => 'label',
                'label' => $this->translator->trans('method.label') .'<span class="requiredField">*</span>',
                'label_html' => true,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            // ->add('birdSpecies', EntityType::class, [
            //     'class' => BirdSpecies::class,
            //     'choice_label' => function(BirdSpecies $birdSpecy) {
            //         return $birdSpecy->getScientificName() . ' ('. $birdSpecy->getBirdFamily()->getFamily() . ')' . $birdSpecy->getImageFile();
            //     },
            //     'label' => 'Espèce oiseau',
            //     'multiple' => true,
            //     'expanded' => true,
            // ])
            ->add('birdSpeciesCounts', CollectionType::class, [
                'entry_type' => BirdSpeciesCountType::class,
                // 'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                // 'label' => 'Oiseaux comptées :',
                'label' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CollectedData::class,
        ]);
    }
}
