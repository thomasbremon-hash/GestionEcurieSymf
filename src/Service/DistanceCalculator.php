<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceCalculator
{
    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function calculate(string $from, string $to): ?float
    {
        $a = $this->geocode($from);
        $b = $this->geocode($to);

        if (!$a || !$b) {
            return null;
        }

        return $this->haversine(
            $a['lat'],
            $a['lon'],
            $b['lat'],
            $b['lon']
        );
    }

    private function geocode(string $address): ?array
    {
        $response = $this->client->request(
            'GET',
            'https://nominatim.openstreetmap.org/search',
            [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1
                ],
                'headers' => [
                    'User-Agent' => 'SymfonyApp'
                ]
            ]
        );

        $data = $response->toArray(false);

        if (empty($data)) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        return $earth * (2 * asin(sqrt($a)));
    }
}
