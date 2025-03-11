<?php

namespace App\Controller;

use App\Entity\NatureReserve;
use App\Entity\SiteCollection;
use App\Form\NatureReserveType;
use App\Repository\CountingCampaignRepository;
use App\Repository\NatureReserveRepository;
use App\Repository\SiteCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user/nature/reserve')]
class NatureReserveController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    #[Route('/', name: 'app_nature_reserve_index', methods: ['GET'])]
    public function index(NatureReserveRepository $natureReserveRepository): Response
    {
        $user = $this->getUser();
        // Vérifier si l'utilisateur a le rôle ADMIN
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VIEW')) {
            $natureReserves = $natureReserveRepository->findAll();
        } else {
            $natureReserves = $natureReserveRepository->findByUser($user);
        }
        
        return $this->render('nature_reserve/index.html.twig', [
            'nature_reserves' => $natureReserves,
        ]);
    }

    #[Route('/new', name: 'app_nature_reserve_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $natureReserve = new NatureReserve();
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    if (!$this->valideNatureReserve($natureReserve)) {
                        return $this->redirectToRoute('app_nature_reserve_new', [], Response::HTTP_SEE_OTHER);
                    }                                             
                    $natureReserve->setCreatedAt(new \DateTimeImmutable());
                    $natureReserve->setCreatedBy($user);
                    $entityManager->persist($natureReserve);
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('nature_reserve.msg.created_success'));
        
                    return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('nature_reserve.msg.created_error') . $e->getMessage());
                    return $this->redirectToRoute('app_nature_reserve_new', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error',$this->translator->trans('invalid_form'));
            }
        }

        return $this->render('nature_reserve/new.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_nature_reserve_show', methods: ['GET'])]
    public function show(SiteCollection $siteCollection, SiteCollectionRepository $siteCollectionRepository, NatureReserveRepository $natureReserveRepository, NatureReserve $natureReserve, CountingCampaignRepository $countingCampaignRepository): Response
    {
        $reserveCampaigns = $natureReserveRepository->getCampaignBySiteOfReserve($natureReserve);
        
        $campaignsBySite = [];
        foreach ($natureReserve->getSiteCollections() as $site) {
            // Utilisation de votre repository pour récupérer les campagnes pour ce site
            $campaignsBySite[$site->getId()] = $siteCollectionRepository->getCampaignsBySiteCollection($site);
        }
        // foreach ($reserveCampaigns as $rsvCampaign) {
        //     $siteCollectionsByCampaign = $siteCollectionRepository->getSiteCollectionsByCampaign($rsvCampaign);
        //     $campaignsBySite[$rsvCampaign['campaignId']] = $siteCollectionsByCampaign;
        // }

        return $this->render('nature_reserve/show.html.twig', [
            'nature_reserve' => $natureReserve,
            'reserveCampaigns' => $reserveCampaigns,
            'campaignsBySite' => $campaignsBySite,
            // 'campaignsBySiteCollection' => $siteCollectionRepository->getCampaignsBySiteCollection($siteCollection),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_nature_reserve_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NatureReserveType::class, $natureReserve);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    if (!$this->valideNatureReserve($natureReserve)) {
                        return $this->redirectToRoute('app_nature_reserve_edit', ['id' => $natureReserve->getId()], Response::HTTP_SEE_OTHER);
                    }
                    $natureReserve->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('nature_reserve.msg.updated_success'));
        
                    return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_nature_reserve_edit', ['id' => $natureReserve->getId()], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', $this->translator->trans('invalid_form'));
            }
        }

        return $this->render('nature_reserve/edit.html.twig', [
            'nature_reserve' => $natureReserve,
            'form' => $form,
        ]);
    }

    /**
     * Valide la reserve naturelle
     *
     * @param NatureReserve $natureReserve
     * @return bool
     */
    private function valideNatureReserve(NatureReserve $natureReserve): bool
    {
        if ($natureReserve->getReserveName() === null) {
            $this->translator->trans('nature_reserve.reserve_name_required');
            return false;
        }
        if ($natureReserve->getSiteCollections()->isEmpty()) {
            $this->addFlash('error', $this->translator->trans('nature_reserve.select_at_least_one_site'));
            return false;
        }

        if ($natureReserve->getReserveLeader() === null) {
            $this->addFlash('error', $this->translator->trans('nature_reserve.select_at_least_one_site'));
            return false;
        }

        foreach ($natureReserve->getSiteCollections() as $siteCollection) {
            $siteCollection->setNatureReserve($natureReserve);
            $siteCollection->setUpdatedAt(new \DateTimeImmutable());
        }

        return true;
    }

    #[Route('/{id}', name: 'app_nature_reserve_delete', methods: ['POST'])]
    public function delete(Request $request, NatureReserve $natureReserve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$natureReserve->getId(), $request->getPayload()->get('_token'))) {
            try {
                if (!$natureReserve->getSiteCollections()->isEmpty()) {
                    foreach ($natureReserve->getSiteCollections() as $siteCollection) {
                        $siteCollection->setNatureReserve(null);
                        $entityManager->persist($siteCollection);
                    }
                }
                $entityManager->remove($natureReserve);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans('nature_reserve.msg.deleted_success'));
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash('error',$this->translator->trans('nature_reserve.msg.deleted_error'));
        }
        return $this->redirectToRoute('app_nature_reserve_index', [], Response::HTTP_SEE_OTHER);
    }
}
