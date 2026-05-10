<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private const API_URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Get weather data for a city
     * 
     * @param string $city City name
     * @param string|null $country Country name
     * @return array Weather data
     */
    public function getWeatherByCity(string $city, ?string $country = null): array
    {
        try {
            // Get coordinates for the city
            $coordinates = $this->getCityCoordinates($city, $country);
            
            if (!$coordinates) {
                return [
                    'success' => false,
                    'error' => 'City not found'
                ];
            }

            // Get weather data using coordinates
            return $this->getWeatherByCoordinates($coordinates['lat'], $coordinates['lon'], $city);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get coordinates for a city using geocoding
     */
    private function getCityCoordinates(string $city, ?string $country = null): ?array
    {
        try {
            $query = $city;
            if ($country) {
                $query .= ', ' . $country;
            }

            $response = $this->httpClient->request('GET', 'https://geocoding-api.open-meteo.com/v1/search', [
                'query' => [
                    'name' => $query,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json'
                ]
            ]);

            $data = $response->toArray();

            if (empty($data['results'])) {
                return null;
            }

            return [
                'lat' => $data['results'][0]['latitude'],
                'lon' => $data['results'][0]['longitude'],
                'name' => $data['results'][0]['name'] ?? $city
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get weather data by coordinates
     */
    private function getWeatherByCoordinates(float $lat, float $lon, string $cityName): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m',
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
                    'timezone' => 'auto',
                    'forecast_days' => 3
                ]
            ]);

            $data = $response->toArray();

            return [
                'success' => true,
                'city' => $cityName,
                'coordinates' => [
                    'latitude' => $lat,
                    'longitude' => $lon
                ],
                'current' => [
                    'temperature' => $data['current']['temperature_2m'] ?? null,
                    'feels_like' => $data['current']['apparent_temperature'] ?? null,
                    'humidity' => $data['current']['relative_humidity_2m'] ?? null,
                    'precipitation' => $data['current']['precipitation'] ?? null,
                    'wind_speed' => $data['current']['wind_speed_10m'] ?? null,
                    'weather_code' => $data['current']['weather_code'] ?? null,
                    'description' => $this->getWeatherDescription($data['current']['weather_code'] ?? 0),
                    'unit' => '°C'
                ],
                'forecast' => $this->formatForecast($data['daily'] ?? []),
                'timezone' => $data['timezone'] ?? 'UTC'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch weather data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format forecast data
     */
    private function formatForecast(array $daily): array
    {
        if (empty($daily)) {
            return [];
        }

        $forecast = [];
        $count = count($daily['time'] ?? []);

        for ($i = 0; $i < $count; $i++) {
            $forecast[] = [
                'date' => $daily['time'][$i] ?? null,
                'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                'precipitation' => $daily['precipitation_sum'][$i] ?? null,
                'weather_code' => $daily['weather_code'][$i] ?? null,
                'description' => $this->getWeatherDescription($daily['weather_code'][$i] ?? 0)
            ];
        }

        return $forecast;
    }

    /**
     * Get weather description from WMO weather code
     */
    private function getWeatherDescription(int $code): string
    {
        return match($code) {
            0 => 'Clear sky',
            1, 2, 3 => 'Partly cloudy',
            45, 48 => 'Foggy',
            51, 53, 55 => 'Drizzle',
            61, 63, 65 => 'Rain',
            71, 73, 75 => 'Snow',
            77 => 'Snow grains',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown'
        };
    }
}
