<?php

namespace App\Controller;

use Monolog\DateTimeImmutable;
use DateTime;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use App\Form\CoverageType;
use App\Form\BirdLifeTaxTreatType;
use App\Form\IucnRedListCategoryType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\BirdSpecies;
use App\Form\BirdSpeciesType;
use App\Repository\BirdSpeciesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bird/species')]
#[IsGranted('ROLE_COLLECTOR')]
class BirdSpeciesController extends AbstractController
{
    #[Route('/', name: 'app_bird_species_index', methods: ['GET'])]
    public function index(BirdSpeciesRepository $birdSpeciesRepository): Response
    {
        return $this->render('bird_species/index.html.twig', [
            'bird_species' => $birdSpeciesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_bird_species_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $birdSpecy = new BirdSpecies();
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $birdSpecy->setImageFile($imageFile);
            }
            $birdSpecy->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', "Espèse d'oiseau a bien été crée");

            return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
        }

        $coverageForm = $this->createForm(CoverageType::class);
        $birdLifeTaxTreatForm = $this->createForm(BirdLifeTaxTreatType::class);
        $iucnRedListCategoryForm = $this->createForm(IucnRedListCategoryType::class);

        return $this->render('bird_species/new.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
            'coverageForm' => $coverageForm->createView(),
            'birdLifeTaxTreatForm' => $birdLifeTaxTreatForm->createView(),
            'iucnRedListCategoryForm' => $iucnRedListCategoryForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_show', methods: ['GET'])]
    public function show(BirdSpecies $birdSpecy): Response
    {
        return $this->render('bird_species/show.html.twig', [
            'bird_specy' => $birdSpecy,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bird_species_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        
        $form = $this->createForm(BirdSpeciesType::class, $birdSpecy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $birdSpecy->setImageFile($imageFile);
            }
            $birdSpecy->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "L'espèse a bien été modifié");

            return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bird_species/edit.html.twig', [
            'bird_specy' => $birdSpecy,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bird_species_delete', methods: ['POST'])]
    public function delete(Request $request, BirdSpecies $birdSpecy, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$birdSpecy->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($birdSpecy);
            $entityManager->flush();
            $this->addFlash('success', "L'espèse a bien été supprimée");
        }

        return $this->redirectToRoute('app_bird_species_index', [], Response::HTTP_SEE_OTHER);
    }
}
