<?php

namespace App\Service;

use App\Repository\CategoryRepository;

class CategorySuggestionService
{
    private array $categoryKeywords = [
        'Plumbing' => ['plumb', 'pipe', 'leak', 'drain', 'faucet', 'toilet', 'sink', 'water'],
        'Electrical' => ['electric', 'wire', 'light', 'outlet', 'switch', 'circuit', 'power'],
        'Gardening' => ['garden', 'lawn', 'plant', 'tree', 'grass', 'hedge', 'landscape'],
        'Cleaning' => ['clean', 'wash', 'mop', 'vacuum', 'dust', 'sanitize', 'tidy'],
        'Painting' => ['paint', 'brush', 'wall', 'color', 'coat', 'decor'],
        'Moving' => ['move', 'transport', 'carry', 'relocate', 'delivery', 'haul'],
        'Tutoring' => ['tutor', 'teach', 'lesson', 'study', 'learn', 'education', 'homework'],
        'IT Support' => ['computer', 'laptop', 'software', 'tech', 'repair', 'install', 'network'],
        'Power Tools' => ['drill', 'saw', 'grinder', 'sander', 'electric tool'],
        'Hand Tools' => ['hammer', 'screwdriver', 'wrench', 'pliers', 'manual tool'],
        'Garden Tools' => ['mower', 'trimmer', 'rake', 'shovel', 'hoe', 'pruner'],
        'Ladders' => ['ladder', 'step', 'scaffold', 'height', 'climb'],
        'Cleaning Equipment' => ['vacuum', 'pressure washer', 'carpet cleaner', 'steam'],
        'Measuring Tools' => ['measure', 'level', 'tape', 'ruler', 'laser'],
        'Outdoor Equipment' => ['tent', 'camping', 'bbq', 'grill', 'outdoor'],
        'Party Equipment' => ['party', 'event', 'chair', 'table', 'decoration', 'tent'],
    ];

    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function suggestCategory(string $text, string $type): array
    {
        $text = strtolower($text);
        $suggestions = [];

        foreach ($this->categoryKeywords as $categoryName => $keywords) {
            $matchCount = 0;
            $totalKeywords = count($keywords);

            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $confidence = round(($matchCount / $totalKeywords) * 100);
                
                $category = $this->categoryRepository->findOneBy([
                    'name' => $categoryName,
                    'type' => $type
                ]);

                if ($category) {
                    $suggestions[] = [
                        'category' => $category,
                        'confidence' => $confidence,
                        'matchedKeywords' => $matchCount
                    ];
                }
            }
        }

        usort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($suggestions, 0, 3);
    }
}
