<?php

namespace App\Controller\Front;

use App\Form\SearchFilterType;
use App\Repository\FavoriteRepository;
use App\Repository\ToolRepository;
use App\Service\QualityScoreService;
use App\Service\RecommendationService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ToolController extends AbstractController
{
    #[Route('/tools', name: 'app_tools')]
    public function index(
        Request $request, 
        ToolRepository $toolRepository, 
        QualityScoreService $qualityScoreService, 
        FavoriteRepository $favoriteRepository,
        PaginatorInterface $paginator
    ): Response {
        // Create the search form
        $form = $this->createForm(SearchFilterType::class, null, ['type' => 'tool']);
        $form->handleRequest($request);

        // Get filter values
        $query = $form->get('query')->getData();
        $category = $form->get('category')->getData();
        $location = $form->get('location')->getData();
        $minPrice = $form->get('minPrice')->getData();
        $maxPrice = $form->get('maxPrice')->getData();
        $sortBy = $form->get('sortBy')->getData();

        // Get QueryBuilder for filtered tools
        $queryBuilder = $toolRepository->findBySearchFiltersQuery(
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

        // Calculate quality scores and check favorites for each tool
        $toolsWithScores = [];
        $user = $this->getUser();
        foreach ($pagination as $tool) {
            $isFavorite = false;
            if ($user) {
                $isFavorite = $favoriteRepository->isFavorite($user, 'tool', $tool->getId());
            }
            
            $toolsWithScores[] = [
                'tool' => $tool,
                'qualityScore' => $qualityScoreService->calculateToolScore($tool),
                'isFavorite' => $isFavorite,
            ];
        }

        return $this->render('front/tools/index.html.twig', [
            'toolsWithScores' => $toolsWithScores,
            'pagination' => $pagination,
            'searchForm' => $form->createView(),
        ]);
    }

    #[Route('/tools/{id}', name: 'app_tool_show')]
    public function show(
        int $id, 
        ToolRepository $toolRepository, 
        QualityScoreService $qualityScoreService,
        RecommendationService $recommendationService
    ): Response {
        $tool = $toolRepository->find($id);

        if (!$tool || !$tool->getIsActive()) {
            throw $this->createNotFoundException('Tool not found');
        }

        $qualityScore = $qualityScoreService->calculateToolScore($tool);
        $recommendations = $recommendationService->getToolRecommendations($tool, 3);

        return $this->render('front/tools/show.html.twig', [
            'tool' => $tool,
            'qualityScore' => $qualityScore,
            'recommendations' => $recommendations,
        ]);
    }
}
