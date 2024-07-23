<?php

namespace App\Form;

use App\Entity\AgentsGroup;
use App\Entity\Country;
use App\Entity\User;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
// use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
// use Symfony\Component\Form\FormEvent;
// use Symfony\Component\Form\FormEvents;
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
                'label' => 'Choisir les membres du groupe :',
                'multiple' => true,
                'required' => true,
                'expanded' => true,
                'attr' => ['id' => 'group_member']
            ])
            // ->add('leader', ChoiceType::class, [
            //     'label' => 'Chef du groupe',
            //     'choices' => [],
            //     // 'placeholder' => '-- Choisir le chef du groupe --',
            //     'attr' => ['id' => 'group_leader']
            // ])
            ->add('leader', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Chef du groupe',
                'placeholder' => '-- Choisir le chef du groupe --',
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => function (Country $country) {
                    return $country->getName() . ' (' . $country->getIso2() . ')';
                },
                'label' => "Pays",
                'placeholder' => '-- Choisir le pays du groupe',
                'required' => true,
                'query_builder' => function (CountryRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC');
                },
            ])
        ;
        
        // // Add an event listener to populate the leader choices based on group members
        // $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
        //     $form = $event->getForm();
        //     $data = $event->getData();

        //     $members = $data->getGroupMember()->toArray();

        //     $form->add('leader', ChoiceType::class, [
        //         'label' => 'Chef du groupe',
        //         'choices' => $members,
        //         'choice_label' => function ($user) {
        //             return $user->getEmail();
        //         },
        //         'placeholder' => '-- Choisir le chef du groupe --',
        //     ]);
        // });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentsGroup::class,
        ]);
    }
}
