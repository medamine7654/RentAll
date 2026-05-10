<?php

namespace App\Service;

use App\Entity\Service;
use App\Entity\Tool;
use App\Entity\Logement;
use App\Repository\ServiceRepository;
use App\Repository\ToolRepository;
use App\Repository\LogementRepository;

/**
 * AI-Powered Recommendation Service
 * Provides smart recommendations based on similarity scoring algorithm
 */
class RecommendationService
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private ToolRepository $toolRepository,
        private LogementRepository $logementRepository
    ) {}

    /**
     * Get recommendations for a Service
     */
    public function getServiceRecommendations(Service $service, int $limit = 3): array
    {
        return $this->serviceRepository->findSimilar($service, $limit);
    }

    /**
     * Get recommendations for a Tool
     */
    public function getToolRecommendations(Tool $tool, int $limit = 3): array
    {
        return $this->toolRepository->findSimilar($tool, $limit);
    }

    /**
     * Get recommendations for a Logement
     */
    public function getLogementRecommendations(Logement $logement, int $limit = 3): array
    {
        return $this->logementRepository->findSimilar($logement, $limit);
    }

    /**
     * Calculate price range for similarity matching (±25%)
     */
    public function calculatePriceRange(float $price): array
    {
        return [
            'min' => $price * 0.75,  // -25%
            'max' => $price * 1.25   // +25%
        ];
    }
}
