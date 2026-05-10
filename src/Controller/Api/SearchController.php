<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/search')]
class SearchController extends AbstractController
{
    #[Route('/logements', name: 'api_search_logements', methods: ['GET'])]
    public function searchLogements(
        Request $request,
        LogementRepository $logementRepository,
        AvisRepository $avisRepository
    ): JsonResponse {
        $query    = $request->query->get('q', '');
        $location = $request->query->get('location', '');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $guests   = $request->query->get('guests');
        // $category filter skipped — no 'type' column in DB

        // isActive replaces old 'disponible' (no such column in DB)
        $queryBuilder = $logementRepository->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->setParameter('active', true);

        if ($query) {
            $queryBuilder->andWhere('l.titre LIKE :query OR l.description LIKE :query OR l.adresse LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($location) {
            $queryBuilder->andWhere('l.adresse LIKE :location OR l.titre LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($minPrice) {
            $queryBuilder->andWhere('l.prixParNuit >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice) {
            $queryBuilder->andWhere('l.prixParNuit <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        if ($guests) {
            // maxGuests replaces old 'capacite' (no such column in DB)
            $queryBuilder->andWhere('l.maxGuests >= :guests')
                ->setParameter('guests', $guests);
        }

        $logements = $queryBuilder->setMaxResults(20)->getQuery()->getResult();

        $results = [];
        foreach ($logements as $logement) {
            $avgRating = $avisRepository->getAverageRatingForLogement($logement);
            $totalAvis = count($avisRepository->findByLogement($logement));

            $results[] = [
                'id'             => $logement->getId(),
                'titre'          => $logement->getTitre(),
                'description'    => substr((string) $logement->getDescription(), 0, 150) . '...',
                'adresse'        => $logement->getAdresse(),
                'prixParNuit'    => $logement->getPrixParNuit(),
                'nombreChambres' => $logement->getNombreChambres(),
                'capacite'       => $logement->getMaxGuests(), // alias
                'type'           => null, // no type column in DB
                'image'          => $logement->getImageName(),
                'rating'         => [
                    'average' => $avgRating ? round($avgRating, 1) : null,
                    'total'   => $totalAvis,
                ],
                'url' => $this->generateUrl('app_logement_show', ['id' => $logement->getId()]),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'count'   => count($results),
            'results' => $results,
        ]);
    }

    #[Route('/suggestions', name: 'api_search_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request, LogementRepository $logementRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['suggestions' => []]);
        }

        // isActive replaces old 'disponible'
        $logements = $logementRepository->createQueryBuilder('l')
            ->where('l.isActive = :active')
            ->andWhere('l.titre LIKE :query OR l.adresse LIKE :query')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $suggestions = [];
        foreach ($logements as $logement) {
            $suggestions[] = [
                'id'     => $logement->getId(),
                'titre'  => $logement->getTitre(),
                'adresse' => $logement->getAdresse(),
                'type'   => null, // no type column in DB
            ];
        }

        return new JsonResponse(['suggestions' => $suggestions]);
    }
}
