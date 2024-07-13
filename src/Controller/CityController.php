<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\ImportCsvType;
use App\Service\FileUploader;
use Monolog\DateTimeImmutable;
use DateTime;

use App\Entity\Country;
use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/city')]
#[IsGranted('ROLE_COLLECTOR', message: 'Vous n\'avez pas l\'accès.')]
class CityController extends AbstractController
{

    #[Route('/', name: 'app_city_index', methods: ['GET', 'POST'])]
    public function index(CityRepository $cityRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImportCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                try {
                    $this->processCsv($csvFile, $entityManager);
                    $this->addFlash('success', 'Les données ont été importées avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_city_index');
            }
        }
        return $this->render('city/index.html.twig', [
            'cities' => $cityRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    private function processCsv(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    {
        $csvData = file_get_contents($csvFile->getPathname());
        $rows = array_map(function($row) {
            return str_getcsv($row, ';');
        }, explode("\n", $csvData));
        
        $headers = array_shift($rows);
    
        foreach ($rows as $row) {
            if (count($row) !== count($headers)) {
                continue; // Skip rows where the number of columns does not match the number of headers
            }
    
            $data = array_combine($headers, $row);
    
            if ($data === false) {
                continue; // Skip rows where array_combine fails
            }
    
            // Fetch or create the Country entity
            $countryName = $data['country'] ?? null;
            $iso2 = $data['iso2'] ?? null;
    
            if ($countryName && $iso2) {
                $countryRepository = $entityManager->getRepository(Country::class);
                $country = $countryRepository->findOneBy(['name' => $countryName, 'iso2' => $iso2]);
    
                if (!$country) {
                    $country = new Country();
                    $country->setName($countryName);
                    $country->setIso2($data['iso2'] ?? ''); // Assuming the CSV has an iso2 column
                    $country->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
    
                    $entityManager->persist($country);
                }
            }
    
            // Check if the city already exists
            $cityName = $data['city'] ?? null;
            if ($cityName) {
                $cityRepository = $entityManager->getRepository(City::class);
                $city = $cityRepository->findOneBy(['name' => $cityName]);
    
                if (!$city) {
                    // Create the City entity if it does not exist
                    $city = new City();
                    $city->setName($cityName);
                    $city->setLatitude($data['lat'] ?? null);
                    $city->setLongitude($data['lng'] ?? null);
                    $city->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
                    $city->setCountry($country ?? null); // Associate the city with the country if available
    
                    $entityManager->persist($city);
                }
            }
        }
    
        $entityManager->flush();
    }
       


    #[Route('/new', name: 'app_city_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $city->setCreatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->persist($city);
            $entityManager->flush();
            $this->addFlash('success', "La ville a bien été créée.");

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_city_show', methods: ['GET'])]
    public function show(City $city): Response
    {
        return $this->render('city/show.html.twig', [
            'city' => $city,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_city_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $city->setUpdatedAt(DateTimeImmutable::createFromMutable(new DateTime()));
            $entityManager->flush();
            $this->addFlash('success', "La ville a bien été modifiée.");

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('city/edit.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_city_delete', methods: ['POST'])]
    public function delete(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$city->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($city);
            $entityManager->flush();
            $this->addFlash('success', "La ville a bien été supprimée.");
        }

        return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
    }
}
