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
    ): JsonResponse
    {
        $query = $request->query->get('q', '');
        $location = $request->query->get('location', '');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $guests = $request->query->get('guests');
        $category = $request->query->get('category');

        $queryBuilder = $logementRepository->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->setParameter('disponible', true);

        // Recherche textuelle
        if ($query) {
            $queryBuilder->andWhere('l.titre LIKE :query OR l.description LIKE :query OR l.adresse LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        // Filtres
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
            $queryBuilder->andWhere('l.capacite >= :guests')
                ->setParameter('guests', $guests);
        }

        if ($category) {
            $queryBuilder->andWhere('l.type = :type')
                ->setParameter('type', $category);
        }

        $logements = $queryBuilder->setMaxResults(20)->getQuery()->getResult();

        // Formater les résultats
        $results = [];
        foreach ($logements as $logement) {
            $avgRating = $avisRepository->getAverageRatingForLogement($logement);
            $totalAvis = count($avisRepository->findByLogement($logement));

            $results[] = [
                'id' => $logement->getId(),
                'titre' => $logement->getTitre(),
                'description' => substr($logement->getDescription(), 0, 150) . '...',
                'adresse' => $logement->getAdresse(),
                'prixParNuit' => $logement->getPrixParNuit(),
                'nombreChambres' => $logement->getNombreChambres(),
                'capacite' => $logement->getCapacite(),
                'type' => $logement->getType(),
                'image' => $logement->getImage(),
                'rating' => [
                    'average' => $avgRating ? round($avgRating, 1) : null,
                    'total' => $totalAvis,
                ],
                'url' => $this->generateUrl('app_logement_show', ['id' => $logement->getId()]),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'count' => count($results),
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

        $logements = $logementRepository->createQueryBuilder('l')
            ->where('l.disponible = :disponible')
            ->andWhere('l.titre LIKE :query OR l.adresse LIKE :query')
            ->setParameter('disponible', true)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $suggestions = [];
        foreach ($logements as $logement) {
            $suggestions[] = [
                'id' => $logement->getId(),
                'titre' => $logement->getTitre(),
                'adresse' => $logement->getAdresse(),
                'type' => $logement->getType(),
            ];
        }

        return new JsonResponse(['suggestions' => $suggestions]);
    }
}
