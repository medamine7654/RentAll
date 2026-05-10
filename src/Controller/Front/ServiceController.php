<?php

namespace App\Controller\Front;

use App\Form\SearchFilterType;
use App\Repository\FavoriteRepository;
use App\Repository\ServiceRepository;
use App\Service\QualityScoreService;
use App\Service\RecommendationService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServiceController extends AbstractController
{
    #[Route('/services', name: 'app_services')]
    public function index(
        Request $request, 
        ServiceRepository $serviceRepository, 
        QualityScoreService $qualityScoreService, 
        FavoriteRepository $favoriteRepository,
        PaginatorInterface $paginator
    ): Response {
        // Create the search form
        $form = $this->createForm(SearchFilterType::class, null, ['type' => 'service']);
        $form->handleRequest($request);

        // Get filter values
        $query = $form->get('query')->getData();
        $category = $form->get('category')->getData();
        $location = $form->get('location')->getData();
        $minPrice = $form->get('minPrice')->getData();
        $maxPrice = $form->get('maxPrice')->getData();
        $sortBy = $form->get('sortBy')->getData();

        // Get QueryBuilder for filtered services
        $queryBuilder = $serviceRepository->findBySearchFiltersQuery(
            $query,
            $category,
            $location,
            $minPrice,
            $maxPrice,
            $sortBy
        );

        // Paginate results
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12
        );

        // Calculate quality scores and check favorites for each service
        $servicesWithScores = [];
        $user = $this->getUser();
        foreach ($pagination as $service) {
            $isFavorite = false;
            if ($user) {
                $isFavorite = $favoriteRepository->isFavorite($user, 'service', $service->getId());
            }
            
            $servicesWithScores[] = [
                'service' => $service,
                'qualityScore' => $qualityScoreService->calculateServiceScore($service),
                'isFavorite' => $isFavorite,
            ];
        }

        return $this->render('front/services/index.html.twig', [
            'servicesWithScores' => $servicesWithScores,
            'pagination' => $pagination,
            'searchForm' => $form->createView(),
        ]);
    }

    #[Route('/services/{id}', name: 'app_service_show')]
    public function show(
        int $id, 
        ServiceRepository $serviceRepository, 
        QualityScoreService $qualityScoreService,
        RecommendationService $recommendationService
    ): Response {
        $service = $serviceRepository->find($id);

        if (!$service || !$service->getIsActive()) {
            throw $this->createNotFoundException('Service not found');
        }

        $qualityScore = $qualityScoreService->calculateServiceScore($service);
        $recommendations = $recommendationService->getServiceRecommendations($service, 3);

        return $this->render('front/services/show.html.twig', [
            'service' => $service,
            'qualityScore' => $qualityScore,
            'recommendations' => $recommendations,
        ]);
    }
}


