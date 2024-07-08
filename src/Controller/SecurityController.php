<?php

namespace App\Controller;

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

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Ce champ de méthode peut être vide - il sera intercepté par la clé de déconnexion de votre pare-feu.');
    }

    #[Route(path: '/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('security/profile.html.twig', [
            'user' => $user,
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
