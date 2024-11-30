<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security, TranslatorInterface $translator): Response
    {
        // Vérifier si l'utilisateur est déjà authentifié
        if ($security->getUser() && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', $translator->trans('You_are_already_logged_in_as') . $this->getUser());
            return $this->redirectToRoute('home');
        }
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Récupérer la locale depuis la session
            $_locale = $request->getSession()->get('_locale', 'fr'); // valeur par défaut
            $user->setLocale($_locale); // Définit la locale de l'utilisateur
            $user->setCreatedAt(new \DateTimeImmutable());
            
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Optionnel : enregistrer la locale dans la session (facultatif)
            $request->getSession()->set('_locale', $_locale);

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_counting_campaign_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/{_locale}/register', name: 'change_locale_register', requirements: ['_locale' => 'en|fr'])]
    public function changeLocaleOut(EntityManagerInterface $entityManager, Request $request, $_locale): RedirectResponse
    {
        // Enregistrer la locale dans la session
        $request->getSession()->set('_locale', $_locale);

        // Rediriger l'utilisateur vers la page précédente
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer ?: $this->generateUrl('app_register'));
    }
}
