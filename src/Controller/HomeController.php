<?php

namespace App\Controller;

use App\Repository\LogoRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(LogoRepository $logoRepository): Response
    {
        $logo = $logoRepository->findOneBy([]); // Fetch the logo
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'logo' => $logo,
        ]);
    }
}
