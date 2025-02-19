<?php

namespace App\Form;

use App\Entity\AgentsGroup;
use App\Entity\SiteAgentsGroup;
use App\Entity\SiteCollection;
use App\Repository\AgentsGroupRepository;
use App\Repository\SiteCollectionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteAgentsGroupType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteCollection', EntityType::class, [
                'class' => SiteCollection::class,
                'label' => $this->translator->trans('siteAgentsGroupType.collection_site').': <span class="requiredField">*</span>',
                'label_html' => true,
                'autocomplete' => true,
                'required' => true,
                'choice_label' => function (SiteCollection $siteCollection) {
                    return $siteCollection->getSiteName() . ' (' . $siteCollection->getCity()->getName() . ' / ' . $siteCollection->getCity()->getRegion()->getName() . ' )';
                },
                'placeholder' => $this->translator->trans('siteAgentsGroupType.choose_site'),
                'query_builder' => function (SiteCollectionRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.siteName', 'ASC'); // Or any other field you want to sort by
                },
            ])
            ->add('agentsGroup', EntityType::class, [
                'class' => AgentsGroup::class,
                'label' => $this->translator->trans('siteAgentsGroupType.groups').': <span class="requiredField">*</span>',
                'label_html' => true,
                'required' => true,
                'choice_label' => function (AgentsGroup $agentsGroup) {
                    return $agentsGroup->getGroupName() . ' / ' . $agentsGroup->getLeader()->getName() . ' ' . $agentsGroup->getLeader()->getLastName();
                },
                'multiple' => true,
                'autocomplete' => true,
                // 'placeholder' => '-- Choisir le(s) groupe(s) --',
                'query_builder' => function (AgentsGroupRepository $repository) {
                    return $repository->createQueryBuilder('b')
                        ->orderBy('b.groupName', 'DESC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteAgentsGroup::class,
        ]);
    }
}
