<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/weather', name: 'api_weather_')]
class WeatherApiController extends AbstractController
{
    public function __construct(
        private WeatherService $weatherService,
        private LogementRepository $logementRepository
    ) {
    }

    #[Route('/city/{city}', name: 'city', methods: ['GET'])]
    public function getWeatherByCity(string $city): JsonResponse
    {
        $weatherData = $this->weatherService->getWeatherByCity($city);

        return $this->json($weatherData);
    }
}
