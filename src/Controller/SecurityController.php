<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
// use App\Repository\ImageRepository;
// use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
// use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Monolog\DateTimeImmutable;
use DateTime;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\EditPasswordType;
use App\Form\UserType;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, Security $security, TranslatorInterface $translator): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // // Vérifier si l'utilisateur est déjà authentifié
        // if ($security->getUser()) {
        //     $user = $security->getUser();

        //     // Vérifier si le statut de l'utilisateur est "Inactif"
        //     if ($user->getUserStatus()->getLabel() !== 'Actif') {
        //         // Ajouter un message flash et rediriger vers la déconnexion
        //         $this->addFlash('warning', $translator->trans('Your_account_is_disabled'));
        //         return $this->redirectToRoute('app_logout');
        //     } else {
        //         // Si l'utilisateur est actif, afficher un message d'information
        //         $this->addFlash('info', $translator->trans('You_are_already_logged_in_as') . ' ' . $lastUsername);
        //         return $this->redirectToRoute('home');
        //     }
        // }

        // Enregistrer la locale dans la session (facultatif)
        $_locale = $request->getSession()->get('_locale', 'fr'); // valeur par défaut
        $request->getSession()->set('_locale', $_locale);
        
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/{_locale}/login', name: 'change_locale_login', requirements: ['_locale' => '[a-zA-Z]{2}'])]
    public function changeLocaleOut(EntityManagerInterface $entityManager, Request $request, $_locale): RedirectResponse
    {
        // Enregistrer la locale dans la session
        $request->getSession()->set('_locale', $_locale);

        // // Obtenir le référent ou définir une redirection par défaut
        // $referer = $request->headers->get('referer');
        // if (!$referer || strpos($referer, $this->generateUrl('change_locale_login')) !== false) {
        //     $referer = $this->generateUrl('app_login');
        // }

        // return new RedirectResponse($referer);
        
        // Rediriger l'utilisateur vers la page précédente
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer ?: $this->generateUrl('app_login'));
    }

    #[Route(path: '/user/logout', name: 'app_logout')]
    public function logout(): void
    {
        // throw new \LogicException('Ce champ de méthode peut être vide - il sera intercepté par la clé de déconnexion de votre pare-feu.');
        throw new \LogicException('Vous étes déconnecté.');
        // $this->addFlash('warning','Vous étes déconnecté.');
    }

    #[Route('/admin/profile', name: 'app_profile', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('security/profile.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }


    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/user/profile/{id}/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        // Rediriger si l'utilisateur n'est pas connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
    
        // Vérifier si l'utilisateur est actif avant de permettre l'édition
        if ($this->getUser()->getUserStatus()->getLabel() !== 'Actif') {
            // throw new CustomUserMessageAuthenticationException('Votre compte est désactivé.');
            $this->addFlash('warning','Votre compte est désactivé.');
            return $this->redirectToRoute('app_logout');
        }
        // Autoriser l'utilisateur à modifier son propre profil ou si c'est un administrateur
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('info', "Vous n'avez pas le droit de modifier ce compte.");
            return $this->redirectToRoute('home');
        }

        // Création du formulaire
        $form = $this->createForm(UserType::class, $user, [
            // Limiter les rôles affichés dans le formulaire si l'utilisateur n'est pas un administrateur
            'show_roles' => $this->isGranted('ROLE_ADMIN'),
            'require_password' => !$this->isGranted('ROLE_ADMIN')  // Si l'administrateur est connecté, le mot de passe n'est pas requis
        ]);
        $form->handleRequest($request);
    
        $formPassword = $this->createForm(EditPasswordType::class, $user);
        $formPassword->handleRequest($request);
    
        // Traitement du formulaire d'édition du profil
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($this->isGranted('ROLE_ADMIN') || ($hasher->isPasswordValid($user, $form->get('password')->getData()))) {
        
                    $imageFile = $form->get('image')['imageFile']->getData(); // Get the uploaded image
                    // Handle image upload only if a new image is provided
                    if ($imageFile) {
                        // If there's already an image, we need to update it
                        if ($user->getImage()) {
                            $image = $user->getImage();
                            $image->setCreatedAt(new \DateTimeImmutable());
                            $image->setImageFile($imageFile); // Update with the new file
                        } else {
                            // If there's no image yet, create a new Image entity
                            $image = new Image();
                            $image->setImageFile($imageFile);
                            $image->setCreatedAt(new \DateTimeImmutable());
                            $entityManager->persist($image);
                            $user->setImage($image); // Set the new image to the user
                        }
                    }
    
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    
                    $entityManager->flush();
                    $this->addFlash('success', 'Les informations du compte ont été bien modifiées');
                    return $this->redirectToRoute('app_profile_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
                } else {
                    $this->addFlash('warning', 'Le mot de passe renseigné est incorrect.');
                }

            } else {
                $this->addFlash('error', $form->getErrors(true));
            }
            
        }
    
        // Traitement du formulaire de changement de mot de passe
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $currentPassword = $formPassword->get('password')->getData();
            $newPassword = $formPassword->get('plainPassword')->getData();
    
            if ($hasher->isPasswordValid($user, $currentPassword)) {
                $user->setUpdatedAt(new \DateTimeImmutable());
                $user->setPassword($hasher->hashPassword($user, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Le mot de passe a été bien modifié.');
                return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('warning', 'Le mot de passe renseigné est incorrect.');
            }
        }
    
        return $this->render('security/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'formPassword' => $formPassword->createView(),
        ]);
    }


    #[IsGranted('ROLE_SUPER_ADMIN', message: 'Vous n\'avez pas l\'accès.')]
    #[Route('/admin/profile/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->get('_token'))) {

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('user.msg.deleted'));
        } else {
            $this->addFlash('error', $translator->trans('user.error.deletion_failed'));
        }

        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
    }


    // public function onAuthenticationSuccess(Request $request, TokenInteface $token, string $firewallName): ?Response
    // {
    //     if ($targetPath =this->getTargetPath($request->getSession(), $firewallName)){
    //         return new RedirectResponse($targetPath);
    //     }

    //     return new RedirectResponse($this->urlGenerator->generate(name: 'app_collected_data_index'));
    //     throw new \Exception(message: 'TODO: provide a valid redirect inside '.__FILE__);
    // }
}
