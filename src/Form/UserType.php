<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Image;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'ROLE_WRITE' => 'ROLE_WRITE',
                    'ROLE_EDIT' => 'ROLE_EDIT',
                    'ROLE_DELETE' => 'ROLE_DELETE',
                    'Collecteur' => 'ROLE_COLLECTOR',
                    'Chef d\'équipe' => 'ROLE_TEAMLEADER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles',
            ])
            // ->add('image', EntityType::class, [
            //     'class' => Image::class,
            //     'choice_label' => 'imageFilename',
            //     'label' => 'Image de profil',
            //     'required' => false,
            // ])
            // ->add('imageFile', VichImageType::class, [
            //     'label' => 'Image (JPG ou PNG)',
            //     'required' => false,
            //     'allow_delete' => true,
            //     'download_uri' => true,
            //     'imagine_pattern' => 'squared_thumbnail_small',
            // ])
            

            // ->add('imageFile', FileType::class, [
            //     'required' => false,
            //     'label' => 'Image de profil (fichiers PNG ou JPG)',
            // ])

            ->add('imageFile', VichImageType::class, [
                'label' => 'Image (JPG ou PNG)',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'mapped' => false,
                'required' => false,
                'download_uri' => false,
                'delete_label' => 'Supprimer',
                'download_label' => 'Télécharger',
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => "Veuillez télécharger un fichier d'image valide (JPEG ou PNG)",
                    ])
                ],
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
                'required' => true,
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
