<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceCalculator
{
    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function calculate(string $addressA, string $addressB): float
    {
        [$lat1, $lon1] = $this->geocode($addressA);
        [$lat2, $lon2] = $this->geocode($addressB);

        return $this->haversine($lat1, $lon1, $lat2, $lon2);
    }

    private function geocode(string $address): array
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
                    'User-Agent' => 'SymfonyDistanceApp/1.0'
                ]
            ]
        );

        $data = $response->toArray();

        if (empty($data)) {
            throw new \RuntimeException('Adresse introuvable : ' . $address);
        }

        return [
            (float) $data[0]['lat'],
            (float) $data[0]['lon']
        ];
    }

    private function haversine(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLon / 2) ** 2;

        return round(
            $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)),
            2
        );
    }
}
