<?php

namespace App\Form;

use App\Entity\BirdSpecies;
use App\Entity\BirdSpeciesCount;
use App\Entity\CollectedData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BirdSpeciesCountType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('birdSpecies', EntityType::class, [
                'class' => BirdSpecies::class,
                'choice_label' => function(BirdSpecies $birdSpecy) {
                    return $birdSpecy->getWispeciescode() . ' - '. $birdSpecy->getScientificName() . ' (' . $birdSpecy->getFrenchName() . '/'. $birdSpecy->getEnglishName() . ')';
                },
                // 'label' => 'Espèce',
                'autocomplete' => true,
                'placeholder' => $this->translator->trans('birdSpecies.code_and_scientific_name'),
                'required' => true,
                'label' => false,
            ])
            ->add('count', IntegerType::class, [
                'label' => false,
                'attr' => [
                    'min' => 0,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BirdSpeciesCount::class,
        ]);
    }
}
