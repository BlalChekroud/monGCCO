<?php

namespace App\Controller;

use App\Entity\SiteAgentsGroup;
use App\Form\SiteAgentsGroupType;
use App\Repository\SiteAgentsGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/site/agents/group')]
class SiteAgentsGroupController extends AbstractController
{
    #[Route('/', name: 'app_site_agents_group_index', methods: ['GET'])]
    public function index(SiteAgentsGroupRepository $siteAgentsGroupRepository): Response
    {
        return $this->render('site_agents_group/index.html.twig', [
            'site_agents_groups' => $siteAgentsGroupRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_site_agents_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $siteAgentsGroup = new SiteAgentsGroup();
        $form = $this->createForm(SiteAgentsGroupType::class, $siteAgentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($siteAgentsGroup);
            $entityManager->flush();

            return $this->redirectToRoute('app_site_agents_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_agents_group/new.html.twig', [
            'site_agents_group' => $siteAgentsGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_agents_group_show', methods: ['GET'])]
    public function show(SiteAgentsGroup $siteAgentsGroup): Response
    {
        return $this->render('site_agents_group/show.html.twig', [
            'site_agents_group' => $siteAgentsGroup,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_site_agents_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SiteAgentsGroup $siteAgentsGroup, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteAgentsGroupType::class, $siteAgentsGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_site_agents_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site_agents_group/edit.html.twig', [
            'site_agents_group' => $siteAgentsGroup,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_agents_group_delete', methods: ['POST'])]
    public function delete(Request $request, SiteAgentsGroup $siteAgentsGroup, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$siteAgentsGroup->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($siteAgentsGroup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_site_agents_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
