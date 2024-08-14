<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Country;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/country')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CountryController extends AbstractController
{
    #[Route('/', name: 'app_country_index', methods: ['GET'])]
    public function index(CountryRepository $countryRepository): Response
    {
        return $this->render('country/index.html.twig', [
            'countries' => $countryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_country_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Ajout de vérifications supplémentaires avant de persister l'entité
                if (empty($country->getName()) || empty($country->getIso2())) {
                    $this->addFlash('error', 'Le nom et le code ISO doivent être remplis.');
                    return $this->redirectToRoute('app_country_new');
                }
                $country->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($country);
                $entityManager->flush();
                $this->addFlash('success', "Le pays a bien été crée");
                return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error',"Une erreur s'est produite lors de la création du pays.");
            }
        }

        return $this->render('country/new.html.twig', [
            'country' => $country,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_country_show', methods: ['GET'])]
    public function show(Country $country): Response
    {
        return $this->render('country/show.html.twig', [
            'country' => $country,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_country_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Country $country, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $country->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                $this->addFlash('success', "Le pays a bien été modifié");
                return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error',"Une erreur s'est produite lors de la modification du pays.");
            }
        }

        return $this->render('country/edit.html.twig', [
            'country' => $country,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_country_delete', methods: ['POST'])]
    public function delete(Request $request, Country $country, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$country->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($country);
            $entityManager->flush();
            $this->addFlash('success', "Le pays a bien été supprimé");
        } else {
            $this->addFlash('error', "Une erreur est survenue");
        }

        return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
    }
}
