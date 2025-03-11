<?php

namespace App\Form;

use App\Entity\NatureReserve;
use App\Entity\SiteCollection;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NatureReserveType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reserveName', TextType::class, [
                'label' => $this->translator->trans('nature_reserve.reserveName').'<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('reserveLeader', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => $this->translator->trans('nature_reserve.reserveLeader').'<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
                'placeholder' => $this->translator->trans('nature_reserve.select_leader'),
                'autocomplete' => true,
            ])
            ->add('siteCollections', EntityType::class, [
                'class' => SiteCollection::class,
                'choice_label' => function (SiteCollection $siteCollection) {
                    return  $siteCollection->getSiteCode() .' ' . $siteCollection->getSiteName() . ' / ' . $siteCollection->getCity()->getName() . ' (' . $siteCollection->getCity()->getRegion()->getRegionCode() . ')';
                },
                'label' => $this->translator->trans('nature_reserve.select_sites').'<span class="requiredField">*</span>',
                'label_html' => true,
                'multiple' => true,
                'required' => true,
                'placeholder' => $this->translator->trans('nature_reserve.select_sites'),
                'autocomplete' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NatureReserve::class,
        ]);
    }
}
