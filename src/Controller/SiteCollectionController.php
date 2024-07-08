<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Monolog\DateTimeImmutable;
use DateTime;
use App\Entity\SiteCollection;
use App\Form\SiteCollectionType;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/site/collection')]
#[IsGranted('ROLE_COLLECTOR')]
class SiteCollectionController extends AbstractController
{
    #[Route('/', name: 'app_site_collection_index', methods: ['GET'])]
    public function index(SiteCollectionRepository $siteCollectionRepository): Response
    {
        return $this->render('site_collection/index.html.twig', [
            'site_collections' => $siteCollectionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_site_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $siteCollection = new SiteCollection();
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteCollection->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($siteCollection);
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été crée");

            return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_collection/new.html.twig', [
            'site_collection' => $siteCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_collection_show', methods: ['GET'])]
    public function show(SiteCollection $siteCollection): Response
    {
        return $this->render('site_collection/show.html.twig', [
            'site_collection' => $siteCollection,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_site_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteCollectionType::class, $siteCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteCollection->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été modifié");

            return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_collection/edit.html.twig', [
            'site_collection' => $siteCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_collection_delete', methods: ['POST'])]
    public function delete(Request $request, SiteCollection $siteCollection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$siteCollection->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($siteCollection);
            $entityManager->flush();
            $this->addFlash('success', "Site de collection a bien été supprimée");
        }

        return $this->redirectToRoute('app_site_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
