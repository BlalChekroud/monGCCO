<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
// use App\Repository\ImageRepository;
// use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
// use Symfony\Component\String\Slugger\SluggerInterface;
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
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/user/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Ce champ de méthode peut être vide - il sera intercepté par la clé de déconnexion de votre pare-feu.');
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
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
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
    
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('info', "Vous n'avez pas le droit de modifier ce compte.");
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
    
        $formPassword = $this->createForm(EditPasswordType::class, $user);
        $formPassword->handleRequest($request);
    
        // Traitement du formulaire d'édition du profil
        if ($form->isSubmitted() && $form->isValid()) {
            if ($hasher->isPasswordValid($user, $form->get('password')->getData())) {
    
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
                $this->addFlash('success', 'Les informations de votre compte ont été bien modifiées');
                return $this->redirectToRoute('app_profile_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('warning', 'Le mot de passe renseigné est incorrect.');
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


    // public function onAuthenticationSuccess(Request $request, TokenInteface $token, string $firewallName): ?Response
    // {
    //     if ($targetPath =this->getTargetPath($request->getSession(), $firewallName)){
    //         return new RedirectResponse($targetPath);
    //     }

    //     return new RedirectResponse($this->urlGenerator->generate(name: 'app_collected_data_index'));
    //     throw new \Exception(message: 'TODO: provide a valid redirect inside '.__FILE__);
    // }
}
