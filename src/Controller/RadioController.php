<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Proxies requests to the Radio Browser API (https://www.radio-browser.info/).
 * This is a free, open-source API — no API key required.
 */
#[Route('/api/radio')]
class RadioController extends AbstractController
{
    private const RADIO_BROWSER_API = 'https://de1.api.radio-browser.info/json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    /**
     * Get radio stations by country code (ISO 3166-1 alpha-2).
     * Example: /api/radio/stations?country=FR&limit=50
     */
    #[Route('/stations', name: 'api_radio_stations', methods: ['GET'])]
    public function stations(Request $request): JsonResponse
    {
        $country = strtoupper(trim($request->query->get('country', '')));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
        $search = trim($request->query->get('search', ''));

        if (!$country && !$search) {
            return $this->json(['error' => 'Country code or search term is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $params = [
                'limit' => $limit,
                'order' => 'votes',
                'reverse' => 'true',
                'hidebroken' => 'true',
            ];

            $endpoint = '/stations/search';

            if ($country) {
                $params['countrycode'] = $country;
            }
            if ($search) {
                $params['name'] = $search;
            }

            $response = $this->httpClient->request('GET', self::RADIO_BROWSER_API . $endpoint, [
                'query' => $params,
                'timeout' => 10,
            ]);

            $stations = $response->toArray();

            // Return only the fields the frontend needs
            $result = array_map(function (array $station) {
                return [
                    'id' => $station['stationuuid'] ?? '',
                    'name' => $station['name'] ?? '',
                    'url' => $station['url_resolved'] ?? $station['url'] ?? '',
                    'favicon' => $station['favicon'] ?? '',
                    'country' => $station['country'] ?? '',
                    'countryCode' => $station['countrycode'] ?? '',
                    'language' => $station['language'] ?? '',
                    'tags' => $station['tags'] ?? '',
                    'codec' => $station['codec'] ?? '',
                    'bitrate' => $station['bitrate'] ?? 0,
                    'votes' => $station['votes'] ?? 0,
                ];
            }, $stations);

            return $this->json(['stations' => $result]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to fetch radio stations.',
                'detail' => $e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * Get available countries with station counts.
     */
    #[Route('/countries', name: 'api_radio_countries', methods: ['GET'])]
    public function countries(): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', self::RADIO_BROWSER_API . '/countries', [
                'query' => ['order' => 'stationcount', 'reverse' => 'true'],
                'timeout' => 10,
            ]);

            $countries = $response->toArray();

            $result = array_map(function (array $c) {
                return [
                    'name' => $c['name'] ?? '',
                    'code' => $c['iso_3166_1'] ?? '',
                    'stationCount' => $c['stationcount'] ?? 0,
                ];
            }, array_filter($countries, fn($c) => ($c['stationcount'] ?? 0) > 0));

            return $this->json(['countries' => array_values($result)]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to fetch countries.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
