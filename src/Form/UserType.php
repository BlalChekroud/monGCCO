<?php

namespace App\Form;

use App\Entity\UserStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajouter le champ 'roles' uniquement si 'show_roles' est vrai
        if ($options['show_roles']) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('user.ROLE_USER') => 'ROLE_USER',
                    $this->translator->trans('user.ROLE_VIEW') => 'ROLE_VIEW',
                    $this->translator->trans('user.ROLE_EDIT') => 'ROLE_EDIT',
                    $this->translator->trans('user.ROLE_CREAT') => 'ROLE_CREAT',
                    $this->translator->trans('user.ROLE_DELETE') => 'ROLE_DELETE',
                    $this->translator->trans('user.ROLE_IMPORT') => 'ROLE_IMPORT',
                    $this->translator->trans('user.ROLE_EXPORT') => 'ROLE_EXPORT',
                    $this->translator->trans('user.ROLE_SUPER_CREAT') => 'ROLE_SUPER_CREAT',
                    $this->translator->trans('user.ROLE_COLLECTOR') => 'ROLE_COLLECTOR',
                    // 'Chef d\'équipe' => 'ROLE_TEAMLEADER',
                    $this->translator->trans('user.ROLE_ADMIN') => 'ROLE_ADMIN',
                    $this->translator->trans('user.ROLE_SUPER_ADMIN') => 'ROLE_SUPER_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => $this->translator->trans('user.role'),
            ])
            ->add('userStatus', EntityType::class, [
                'class' => UserStatus::class,
                'label' => $this->translator->trans('userStatus.label'),
                'choice_label' => 'label',
                'required' => true
            ])
            ;
        }
        $builder
            ->add('image', ImageType::class, [
                'label' => $this->translator->trans('image.insert'),
                // 'label_html' => true,
                'required' => false,
            ])
            ->add('name',TextType::class, [
                'label' => $this->translator->trans('user.firstName'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('user.firstName_blank'),
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => $this->translator->trans('user.firstName_too_short'),
                        // max length allowed by Symfony for security reasons
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('lastName',TextType::class, [
                'label' => $this->translator->trans('user.lastName'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('user.lastName_blank'),
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => $this->translator->trans('user.lastName_too_short'),
                        // max length allowed by Symfony for security reasons
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('phone',TextType::class, [
                'label' => $this->translator->trans('user.phone'),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('user.phone_blank'),
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => $this->translator->trans('user.phone_too_short'),
                        // max length allowed by Symfony for security reasons
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'toggle' => true,
                'hidden_label' => $this->translator->trans('user.hide'),
                'visible_label' => $this->translator->trans('user.show'),
                'attr' => ['autocomplete' => 'new-password'],
                'required' => $options['require_password'],  // Rend le mot de passe facultatif pour les administrateurs
                'constraints' => $options['require_password'] ? [
                    new NotBlank([
                        'message' => $this->translator->trans('user.password_blank'),
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => $this->translator->trans('user.password_too_short'),
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    // new PasswordStrength(
                    //     minScore: PasswordStrength::STRENGTH_STRONG
                    // )
                ] : [],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'show_roles' => false, // Par défaut, le champ de rôles est caché
            'require_password' => true,  // Par défaut, le mot de passe est requis
        ]);
    }
}
