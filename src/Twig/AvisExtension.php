<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AvisExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('stars', [$this, 'renderStars'], ['is_safe' => ['html']]),
        ];
    }

    public function renderStars(int $rating): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '⭐';
            } else {
                $stars .= '☆';
            }
        }
        return $stars;
    }
}
