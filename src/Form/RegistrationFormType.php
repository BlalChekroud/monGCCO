<?php

namespace App\Form;
use App\Validator\PasswordMatch;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'yourEmail',
                    'required' => true,
                ],
            ])
            ->add('name',TextType::class, [
                'label' => false,
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
                'label' => false,
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
                'label' => false,
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
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Accepter les termes et conditions',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devriez accepter nos termes.',
                    ]),
                    // new IsFalse([
                    //     'message' => '2 Vous devriez accepter nos termes.',
                    // ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'toggle' => true,
                'hidden_label' => 'Masquer',
                'visible_label' => 'Afficher',
                // 'visible_icon' => null,
                // 'hidden_icon' => null,
                'label' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
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
                ],
            ])
            ->add('passwordConfirm', PasswordType::class, [
                'mapped' => false,
                'label' => false,
                'toggle' => true,
                'hidden_label' => 'Masquer',
                'visible_label' => 'Afficher',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer votre mot de passe']),
                    new PasswordMatch(),
                ],
            ])
        ;
        // // Ajouter un écouteur d'événement pour la validation après la soumission du formulaire
        // $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
        //     $form = $event->getForm();
        //     $data = $form->getData();

        //     // Obtiens les données des champs
        //     $plainPassword = $form->get('plainPassword')->getData();
        //     $passwordConfirm = $form->get('passwordConfirm')->getData();

        //     // Vérifie si les mots de passe correspondent
        //     if ($plainPassword !== $passwordConfirm) {
        //         $form->get('passwordConfirm')->addError(new FormError('Les mots de passe ne correspondent pas.'));
        //     }
        // });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
