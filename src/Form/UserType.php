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
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajouter le champ 'roles' uniquement si 'show_roles' est vrai
        if ($options['show_roles']) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'ROLE_VIEW' => 'ROLE_VIEW',
                    'ROLE_EDIT' => 'ROLE_EDIT',
                    'ROLE_CREAT' => 'ROLE_CREAT',
                    'ROLE_DELETE' => 'ROLE_DELETE',
                    'ROLE_IMPORT' => 'ROLE_IMPORT',
                    'ROLE_EXPORT' => 'ROLE_EXPORT',
                    'ROLE_SUPER_CREAT' => 'ROLE_SUPER_CREAT',
                    'Collecteur' => 'ROLE_COLLECTOR',
                    // 'Chef d\'équipe' => 'ROLE_TEAMLEADER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles',
            ])
            ->add('userStatus', EntityType::class, [
                'class' => UserStatus::class,
                'label' => $this->translator->trans('userStatus.label'),
                'choice_label' => 'label',
                // 'required' => true
            ])
            ;
        }
        $builder
            ->add('image', ImageType::class, [
                'label' => 'Inserer une image<span class="requiredField">*</span>',
                'label_html' => true,
                'required' => false,
            ])

            // ->add('email', EmailType::class, [
            //     'attr' => [
            //         'class' => 'form-control',
            //         'id' => 'yourEmail',
            //         'required' => true,
            //     ],
            // ])
            ->add('name',TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre nom',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre nom doit avoir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('lastName',TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre prénom',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre prénom doit avoir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('phone',TextType::class, [
                'label' => 'Numéro de téléphone',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre Numéro de téléphone',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Votre numéro de téléphone doit avoir au moins {{ limit }} caractères.',
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
                'hidden_label' => 'Masquer',
                'visible_label' => 'Afficher',
                'attr' => ['autocomplete' => 'new-password'],
                'required' => $options['require_password'],  // Rend le mot de passe facultatif pour les administrateurs
                'constraints' => $options['require_password'] ? [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit avoir au moins {{ limit }} caractères.',
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
