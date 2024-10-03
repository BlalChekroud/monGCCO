<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Logo;
use App\Form\LogoType;
use App\Repository\LogoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/logo')]
class LogoController extends AbstractController
{
    #[Route('/', name: 'app_logo_index', methods: ['GET'])]
    public function index(LogoRepository $logoRepository): Response
    {
        return $this->render('logo/index.html.twig', [
            'logos' => $logoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_logo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LogoRepository $logoRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérifier s'il existe déjà un logo
        $existingLogo = $logoRepository->findOneBy([]); // Find an existing logo, assuming only one should exist globally
        
        if ($existingLogo) {
            // Rediriger ou afficher un message d'erreur si un logo existe déjà
            $this->addFlash('error', 'Un logo a déjà été créé. Vous ne pouvez pas en créer un autre.');
            return $this->redirectToRoute('app_logo_index');
        }

        $logo = new Logo();
        $form = $this->createForm(LogoType::class, $logo);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $imageFile = $form->get('image')['imageFile']->getData(); // Get image file
    
                if ($imageFile) {
                    $image = new Image();
                    $image->setImageFile($imageFile);
                    $image->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($image);
                    $logo->setImage($image);

                }
    
                $logo->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($logo);
                $entityManager->flush();
                $this->addFlash('success', "Le logo a bien été ajouté.");
    
                return $this->redirectToRoute('app_logo_index', [], Response::HTTP_SEE_OTHER);

            } else {
                $this->addFlash('error','Une erreur s\'est produite lors de l\'ajout du logo.');
            }
        }

        return $this->render('logo/new.html.twig', [
            'logo' => $logo,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_logo_show', methods: ['GET'])]
    public function show(Logo $logo): Response
    {
        return $this->render('logo/show.html.twig', [
            'logo' => $logo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logo $logo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LogoType::class, $logo);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                
                $imageFile = $form->get('image')['imageFile']->getData(); // Get the uploaded image
    
                // Handle image upload only if a new image is provided
                if ($imageFile) {
                    // If there's already an image, we need to update it
                    if ($logo->getImage()) {
                        $image = $logo->getImage();
                        $image->setImageFile($imageFile); // Update with the new file
                    } else {
                        // If there's no image yet, create a new Image entity
                        $image = new Image();
                        $image->setImageFile($imageFile);
                        $image->setCreatedAt(new \DateTimeImmutable());
                        $entityManager->persist($image);
                        $logo->setImage($image); // Set the new image to the logo
                    }
                }

                $logo->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', "Le logo a bien été modifié.");

                return $this->redirectToRoute('app_logo_index', [], Response::HTTP_SEE_OTHER);
            }  else {
                $this->addFlash('error','Une erreur s\'est produite lors de la modification du logo.');
            }

        }

        return $this->render('logo/edit.html.twig', [
            'logo' => $logo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_logo_delete', methods: ['POST'])]
    public function delete(Request $request, Logo $logo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logo->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($logo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logo_index', [], Response::HTTP_SEE_OTHER);
    }
}
