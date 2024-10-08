<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\CountingCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class CountingCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de début',
                'required' => true
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de fin',
                'required' => true
            ])
            ->add('siteAgentsGroups', CollectionType::class, [
                'entry_type' => SiteAgentsGroupType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,   // Permet l'ajout dynamique
                'allow_delete' => true, // Permet la suppression dynamique
                'by_reference' => false,
                'label' => false,
            ])
            ->add('description',TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
        ;
        
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $campaign = $event->getData();

            if ($campaign instanceof CountingCampaign) {
                $sites = [];
                foreach ($campaign->getSiteAgentsGroups() as $siteAgentGroup) {
                    $site = $siteAgentGroup->getSiteCollection();
                    if (in_array($site, $sites, true)) {
                        $form->get('siteAgentsGroups')->addError(new FormError('Chaque site ne peut être sélectionné qu\'une seule fois.'));
                    }
                    $sites[] = $site;
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CountingCampaign::class,
        ]);
    }
}
