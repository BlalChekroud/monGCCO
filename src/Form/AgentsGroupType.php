<?php

namespace App\Form;

use App\Repository\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
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
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groupMember', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function (UserRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC');
                },
                // 'label' => $this->translator->trans('choose_group_leader') . ' <span class="requiredField">*</span>',
                'label_html' => true,
                'multiple' => true,
                'required' => true,
                'autocomplete' => true,
                'placeholder' => $this->translator->trans('agentsGroup.choose_group_leader'),
                'attr' => ['id' => 'group_member']
            ])
            ->add('leader', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function (UserRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.name', 'ASC');
                },
                // 'label' => 'Chef du groupe<span class="requiredField">*</span>',
                'label_html' => true,
                'placeholder' =>  $this->translator->trans('agentsGroup.choose_group_leader'),
                'autocomplete' => true,
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => function (Country $country) {
                    return $country->getName() . ' (' . $country->getIso2() . ')';
                },
                // 'label' => 'Pays<span class="requiredField">*</span>',
                'label_html' => true,
                'placeholder' => $this->translator->trans('Select_the_country'),
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
