<?php

namespace App\Service;

use App\Entity\Logement;
use App\Entity\Service;
use App\Entity\Tool;

class QualityScoreService
{
    /**
     * Calculate quality score for a Service
     */
    public function calculateServiceScore(Service $service): array
    {
        $score = 0;
        $maxScore = 100;
        $suggestions = [];
        $checks = [];

        // 1. Has Image (30 points)
        if ($service->getImageName()) {
            $score += 30;
            $checks[] = ['passed' => true, 'message' => 'Has professional image'];
        } else {
            $suggestions[] = 'Add an image to make your listing more attractive';
            $checks[] = ['passed' => false, 'message' => 'Missing image'];
        }

        // 2. Description Length (30 points)
        $description = $service->getDescription();
        if ($description && strlen($description) >= 100) {
            $score += 30;
            $checks[] = ['passed' => true, 'message' => 'Detailed description provided'];
        } elseif ($description && strlen($description) >= 50) {
            $score += 15;
            $suggestions[] = 'Add more details to your description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description could be more detailed'];
        } else {
            $suggestions[] = 'Write a detailed description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description is too short'];
        }

        // 3. Has Category (15 points)
        if ($service->getCategory()) {
            $score += 15;
            $checks[] = ['passed' => true, 'message' => 'Category assigned'];
        } else {
            $suggestions[] = 'Assign a category to help users find your service';
            $checks[] = ['passed' => false, 'message' => 'No category assigned'];
        }

        // 4. Has Location (10 points)
        if ($service->getLocation()) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Location specified'];
        } else {
            $suggestions[] = 'Add your location to attract local customers';
            $checks[] = ['passed' => false, 'message' => 'Location not specified'];
        }

        // 5. Reasonable Price (10 points)
        $price = (float) $service->getBasePrice();
        if ($price > 0 && $price <= 1000) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Price is reasonable'];
        } elseif ($price > 1000) {
            $score += 5;
            $suggestions[] = 'Consider if your price is competitive';
            $checks[] = ['passed' => false, 'message' => 'Price might be too high'];
        } else {
            $suggestions[] = 'Set a reasonable price for your service';
            $checks[] = ['passed' => false, 'message' => 'Price not set'];
        }

        // 6. Recently Created (5 points)
        $daysSinceCreation = (new \DateTime())->diff($service->getCreatedAt())->days;
        if ($daysSinceCreation <= 30) {
            $score += 5;
            $checks[] = ['passed' => true, 'message' => 'Recently added'];
        }

        // Calculate percentage
        $percentage = round(($score / $maxScore) * 100);

        // Determine rating
        $rating = $this->getRating($percentage);

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'rating' => $rating,
            'suggestions' => $suggestions,
            'checks' => $checks,
        ];
    }

    /**
     * Calculate quality score for a Tool
     */
    public function calculateToolScore(Tool $tool): array
    {
        $score = 0;
        $maxScore = 100;
        $suggestions = [];
        $checks = [];

        // 1. Has Image (30 points)
        if ($tool->getImageName()) {
            $score += 30;
            $checks[] = ['passed' => true, 'message' => 'Has professional image'];
        } else {
            $suggestions[] = 'Add an image to make your listing more attractive';
            $checks[] = ['passed' => false, 'message' => 'Missing image'];
        }

        // 2. Description Length (30 points)
        $description = $tool->getDescription();
        if ($description && strlen($description) >= 100) {
            $score += 30;
            $checks[] = ['passed' => true, 'message' => 'Detailed description provided'];
        } elseif ($description && strlen($description) >= 50) {
            $score += 15;
            $suggestions[] = 'Add more details to your description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description could be more detailed'];
        } else {
            $suggestions[] = 'Write a detailed description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description is too short'];
        }

        // 3. Has Category (15 points)
        if ($tool->getCategory()) {
            $score += 15;
            $checks[] = ['passed' => true, 'message' => 'Category assigned'];
        } else {
            $suggestions[] = 'Assign a category to help users find your tool';
            $checks[] = ['passed' => false, 'message' => 'No category assigned'];
        }

        // 4. Has Location (10 points)
        if ($tool->getLocation()) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Location specified'];
        } else {
            $suggestions[] = 'Add your location to attract local renters';
            $checks[] = ['passed' => false, 'message' => 'Location not specified'];
        }

        // 5. Reasonable Price (10 points)
        $price = (float) $tool->getPricePerDay();
        if ($price > 0 && $price <= 500) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Price is reasonable'];
        } elseif ($price > 500) {
            $score += 5;
            $suggestions[] = 'Consider if your price is competitive';
            $checks[] = ['passed' => false, 'message' => 'Price might be too high'];
        } else {
            $suggestions[] = 'Set a reasonable price for your tool';
            $checks[] = ['passed' => false, 'message' => 'Price not set'];
        }

        // 6. Recently Created (5 points)
        $daysSinceCreation = (new \DateTime())->diff($tool->getCreatedAt())->days;
        if ($daysSinceCreation <= 30) {
            $score += 5;
            $checks[] = ['passed' => true, 'message' => 'Recently added'];
        }

        // Calculate percentage
        $percentage = round(($score / $maxScore) * 100);

        // Determine rating
        $rating = $this->getRating($percentage);

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'rating' => $rating,
            'suggestions' => $suggestions,
            'checks' => $checks,
        ];
    }

    /**
     * Calculate quality score for a Logement
     */
    public function calculateLogementScore(Logement $logement): array
    {
        $score = 0;
        $maxScore = 100;
        $suggestions = [];
        $checks = [];

        // 1. Has Image (30 points)
        if ($logement->getImageName()) {
            $score += 30;
            $checks[] = ['passed' => true, 'message' => 'Has professional image'];
        } else {
            $suggestions[] = 'Add an image to make your listing more attractive';
            $checks[] = ['passed' => false, 'message' => 'Missing image'];
        }

        // 2. Description Length (25 points)
        $description = $logement->getDescription();
        if ($description && strlen($description) >= 100) {
            $score += 25;
            $checks[] = ['passed' => true, 'message' => 'Detailed description provided'];
        } elseif ($description && strlen($description) >= 50) {
            $score += 12;
            $suggestions[] = 'Add more details to your description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description could be more detailed'];
        } else {
            $suggestions[] = 'Write a detailed description (at least 100 characters)';
            $checks[] = ['passed' => false, 'message' => 'Description is too short'];
        }

        // 3. Has Category (15 points)
        if ($logement->getCategory()) {
            $score += 15;
            $checks[] = ['passed' => true, 'message' => 'Category assigned'];
        } else {
            $suggestions[] = 'Assign a category to help users find your property';
            $checks[] = ['passed' => false, 'message' => 'No category assigned'];
        }

        // 4. Has Complete Location (10 points)
        if ($logement->getCity() && $logement->getCountry()) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Complete location specified'];
        } elseif ($logement->getCity() || $logement->getCountry()) {
            $score += 5;
            $suggestions[] = 'Add complete location (city and country)';
            $checks[] = ['passed' => false, 'message' => 'Partial location specified'];
        } else {
            $suggestions[] = 'Add your location to attract guests';
            $checks[] = ['passed' => false, 'message' => 'Location not specified'];
        }

        // 5. Has Property Details (10 points)
        $hasDetails = $logement->getNumberOfRooms() || $logement->getNumberOfBeds() || 
                      $logement->getNumberOfBathrooms() || $logement->getMaxGuests();
        if ($hasDetails) {
            $score += 10;
            $checks[] = ['passed' => true, 'message' => 'Property details provided'];
        } else {
            $suggestions[] = 'Add property details (rooms, beds, bathrooms, guests)';
            $checks[] = ['passed' => false, 'message' => 'Missing property details'];
        }

        // 6. Reasonable Price (5 points)
        $price = (float) $logement->getPricePerNight();
        if ($price > 0 && $price <= 1000) {
            $score += 5;
            $checks[] = ['passed' => true, 'message' => 'Price is reasonable'];
        } elseif ($price > 1000) {
            $score += 2;
            $suggestions[] = 'Consider if your price is competitive';
            $checks[] = ['passed' => false, 'message' => 'Price might be too high'];
        } else {
            $suggestions[] = 'Set a reasonable price for your property';
            $checks[] = ['passed' => false, 'message' => 'Price not set'];
        }

        // 7. Recently Created (5 points)
        $daysSinceCreation = (new \DateTime())->diff($logement->getCreatedAt())->days;
        if ($daysSinceCreation <= 30) {
            $score += 5;
            $checks[] = ['passed' => true, 'message' => 'Recently added'];
        }

        // Calculate percentage
        $percentage = round(($score / $maxScore) * 100);

        // Determine rating
        $rating = $this->getRating($percentage);

        return [
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'rating' => $rating,
            'suggestions' => $suggestions,
            'checks' => $checks,
        ];
    }

    /**
     * Get rating based on percentage
     */
    private function getRating(int $percentage): array
    {
        if ($percentage >= 81) {
            return [
                'label' => 'Excellent',
                'color' => 'green',
                'icon' => 'fa-check-circle',
                'bgClass' => 'bg-green-100',
                'textClass' => 'text-green-800',
                'barClass' => 'bg-green-600',
            ];
        } elseif ($percentage >= 51) {
            return [
                'label' => 'Good',
                'color' => 'yellow',
                'icon' => 'fa-star',
                'bgClass' => 'bg-yellow-100',
                'textClass' => 'text-yellow-800',
                'barClass' => 'bg-yellow-500',
            ];
        } else {
            return [
                'label' => 'Needs Improvement',
                'color' => 'red',
                'icon' => 'fa-exclamation-triangle',
                'bgClass' => 'bg-red-100',
                'textClass' => 'text-red-800',
                'barClass' => 'bg-red-500',
            ];
        }
    }
}
