<?php

namespace App\Form;

use App\Entity\AgentsGroup;
use App\Entity\Country;
use App\Entity\User;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentsGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groupMember', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Choisir les membres du groupe<span class="requiredField">*</span>',
                'label_html' => true,
                'multiple' => true,
                'required' => true,
                'autocomplete' => true,
                'placeholder' => true,
                'attr' => ['id' => 'group_member']
            ])
            ->add('leader', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Chef du groupe<span class="requiredField">*</span>',
                'label_html' => true,
                'placeholder' => '-- Choisir le chef du groupe --',
                'autocomplete' => true,
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => function (Country $country) {
                    return $country->getName() . ' (' . $country->getIso2() . ')';
                },
                'label' => 'Pays<span class="requiredField">*</span>',
                'label_html' => true,
                'placeholder' => '-- Choisir le pays du groupe --',
                'required' => true,
                'autocomplete' => true,
                'query_builder' => function (CountryRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentsGroup::class,
        ]);
    }
}
