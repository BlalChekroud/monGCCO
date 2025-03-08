<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Image;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

// #[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
#[Route('/admin/image')]
class ImageController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_image_index', methods: ['GET'])]
    public function index(ImageRepository $imageRepository): Response
    {
        return $this->render('image/index.html.twig', [
            'images' => $imageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // /** @var UploadedFile $imageFile */
                    $imageFile = $form->get('imageFile')->getData();
            
                    if ($imageFile) {
                        $image->setImageFile($imageFile);
                    }
                    $image->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($image);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('image.msg.created_success'));
            
                    return $this->redirectToRoute('app_image_index', [], Response::HTTP_SEE_OTHER);        
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_image_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('image.msg.created_error'));
            }
        }

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'form' => $form,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_image_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Image $image, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // /** @var UploadedFile $imageFile */
                    $imageFile = $form->get('imageFile')->getData();
            
                    if ($imageFile) {
                        $image->setImageFile($imageFile);
                    }
                    $image->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('image.msg.updated_success'));
            
                    return $this->redirectToRoute('app_image_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_image_edit', ['id' => $image->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('image.msg.updated_error'));
            }
        }

        return $this->render('image/edit.html.twig', [
            'image' => $image,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_image_delete', methods: ['POST'])]
    public function delete(Request $request, Image $image, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($image);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('image.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_image_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('image.msg.deleted_error'));
        }

        return $this->redirectToRoute('app_image_index', [], Response::HTTP_SEE_OTHER);
    }
}
