<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class EditPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                ],
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'mapped' => false,
            ]);
            // ->add('password', PasswordType::class, [
            //     'label' => 'Mot de passe actuel',
            //     'mapped' => false,
            // ])
            // ->add('plainPassword', PasswordType::class, [
            //     'label' => 'Nouveau mot de passe',
            //     'mapped' => false,
            // ])
            // ->add('confirmPassword', PasswordType::class, [
            //     'label' => 'Confirmer le nouveau mot de passe',
            //     'mapped' => false,
            // ])
            
            // ->add('password', PasswordType::class, [
            //     // instead of being set onto the object directly,
            //     // this is read and encoded in the controller
            //     'mapped' => false,
            //     'attr' => ['autocomplete' => 'new-password'],
            //     'required' => true,
            //     'constraints' => [
            //         new NotBlank([
            //             'message' => 'Veuillez saisir votre mot de passe',
            //         ]),
            //         new Length([
            //             'min' => 6,
            //             'minMessage' => 'Votre mot de passe doit avoir au moins {{ limit }} caractères.',
            //             // max length allowed by Symfony for security reasons
            //             'max' => 4096,
            //         ]),
            //         // new PasswordStrength(
            //         //     minScore: PasswordStrength::STRENGTH_STRONG
            //         // )
            //     ],
            // ])
            // ->add('plainPassword', RepeatedType::class, [
            //     'type' => PasswordType::class,
            //     'options' => [
            //         'attr' => [
            //             'autocomplete' => 'new-password',
            //             'class' => 'form-control',
            //             'id' => 'newPassword',
            //         ],
            //     ],
            //     'first_options' => [
            //         'constraints' => [
            //             new NotBlank([
            //                 'message' => 'Please enter a password',
            //             ]),
            //             new Length([
            //                 'min' => 6,
            //                 'minMessage' => 'Your password should be at least {{ limit }} characters',
            //                 // max length allowed by Symfony for security reasons
            //                 'max' => 4096,
            //             ]),
            //             new PasswordStrength(),
            //             new NotCompromisedPassword(),
            //         ],
            //         'label' => 'Nouveau',
            //     ],
            //     'second_options' => [
            //         'label' => 'Repeter',
            //     ],
            //     'invalid_message' => 'The password fields must match.',
            //     // Instead of being set onto the object directly,
            //     // this is read and encoded in the controller
            //     'mapped' => false,
            // ])
            // // ->add('newPassword', PasswordType::class, [
            // //     // instead of being set onto the object directly,
            // //     // this is read and encoded in the controller
            // //     'mapped' => false,
            // //     'attr' => ['autocomplete' => 'new-password'],
            // //     'required' => true,
            // //     'constraints' => [
            // //         new NotBlank([
            // //             'message' => 'Veuillez saisir un nouveau mot de passe',
            // //         ]),
            // //         new Length([
            // //             'min' => 6,
            // //             'minMessage' => 'Votre nouveau mot de passe doit avoir au moins {{ limit }} caractères.',
            // //             // max length allowed by Symfony for security reasons
            // //             'max' => 4096,
            // //         ]),
            // //         // new PasswordStrength(
            // //         //     minScore: PasswordStrength::STRENGTH_STRONG
            // //         // )
            // //     ],
            // // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
