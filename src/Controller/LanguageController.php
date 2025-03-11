<?php

namespace App\Controller;

use App\Entity\Language;
use App\Form\LanguageType;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/super-admin/language')]
class LanguageController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_language_index', methods: ['GET'])]
    public function index(LanguageRepository $languageRepository): Response
    {
        return $this->render('language/index.html.twig', [
            'languages' => $languageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_language_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $language = new Language();
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $language->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($language);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans("language.msg.created"));
    
                return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', $translator->trans('language.error.creation_failed'));
            }
        }

        return $this->render('language/new.html.twig', [
            'language' => $language,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_language_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Language $language, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $language->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('language.msg.updated'));
                    return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('language.error.modification_failed') . $e->getMessage());
                    return $this->redirectToRoute('app_language_edit', ['id' => $language->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('invalid_form'));
            }
        }

        return $this->render('language/edit.html.twig', [
            'language' => $language,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_language_delete', methods: ['POST'])]
    public function delete(Request $request, Language $language, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$language->getId(), $request->getPayload()->get('_token'))) {
            try {
                $entityManager->remove($language);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('language.msg.deleted'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('language.error.deletion_failed') . $e->getMessage());
                return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
            }    
        } else {
            $this->addFlash('error',$this->translator->trans('invalid_form'));
        }

        return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
    }
}
