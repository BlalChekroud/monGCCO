<?php

namespace App\Form;

use App\Enum\ExportFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Préparer les choix avec des libellés traduits
        $choices = [];
        foreach (ExportFormat::cases() as $format) {
            $choices[$this->translator->trans('export_format.' . strtolower($format->name))] = $format;
        }

        $builder
            ->add('format', ChoiceType::class, [
                'choices' => $choices,
                'label' => false,
                'placeholder' => $this->translator->trans("choose export format"),
            ])
            ->add('submit', SubmitType::class, [
                'label' => false,
                'attr' => ['class' => 'bi bi-download me-2 text-primary'],
            ])
            // ->add('format', EnumType::class, [
            //     'class' => ExportFormat::class,
            //     'label' => false,
            //     'placeholder' => $this->translator->trans("choose export format"),
            //     // 'attr' => [
            //     //     'onchange' => 'if(this.value !== "") { this.form.submit(); }',  // Vérifie si la valeur n'est pas vide avant de soumettre
            //     // ],
            // ])
            // ->add('submit', SubmitType::class, [
            //     'label' => false,
            //     'attr' => ['class' => 'bi bi-download me-2 text-primary'],
            // ]);
        ;
    }

}
