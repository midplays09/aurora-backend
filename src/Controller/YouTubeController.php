<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/youtube')]
class YouTubeController extends AbstractController
{
    private const YOUTUBE_API = 'https://www.googleapis.com/youtube/v3';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    #[Route('/search', name: 'api_youtube_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $maxResults = min(25, max(1, (int) $request->query->get('maxResults', 20)));

        if ($query === '') {
            return $this->json(['error' => 'Search query is required.'], Response::HTTP_BAD_REQUEST);
        }

        $apiKey = $this->apiKey();
        if (!$apiKey) {
            return $this->json(['error' => 'YouTube search is not configured. Set YOUTUBE_API_KEY on the backend.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $searchResponse = $this->httpClient->request('GET', self::YOUTUBE_API . '/search', [
                'query' => [
                    'part' => 'snippet',
                    'type' => 'video',
                    'q' => $query,
                    'maxResults' => $maxResults,
                    'key' => $apiKey,
                ],
                'timeout' => 10,
            ])->toArray();

            $ids = array_values(array_filter(array_map(
                fn(array $item) => $item['id']['videoId'] ?? null,
                $searchResponse['items'] ?? []
            )));

            if (!$ids) {
                return $this->json(['results' => []]);
            }

            return $this->json(['results' => $this->videoDetails($ids, $apiKey)]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to search YouTube.', 'detail' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/video/{videoId}', name: 'api_youtube_video', methods: ['GET'])]
    public function video(string $videoId): JsonResponse
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) {
            return $this->json(['error' => 'YouTube video details are not configured. Set YOUTUBE_API_KEY on the backend.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $details = $this->videoDetails([$videoId], $apiKey);
            if (!$details) {
                return $this->json(['error' => 'Video not found.'], Response::HTTP_NOT_FOUND);
            }

            return $this->json($details[0]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to load YouTube video.', 'detail' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * @param string[] $ids
     * @return array<int, array<string, mixed>>
     */
    private function videoDetails(array $ids, string $apiKey): array
    {
        $response = $this->httpClient->request('GET', self::YOUTUBE_API . '/videos', [
            'query' => [
                'part' => 'snippet,contentDetails',
                'id' => implode(',', $ids),
                'key' => $apiKey,
            ],
            'timeout' => 10,
        ])->toArray();

        return array_map(function (array $item) {
            $snippet = $item['snippet'] ?? [];
            $thumbnails = $snippet['thumbnails'] ?? [];
            $thumbnail = $thumbnails['medium']['url']
                ?? $thumbnails['default']['url']
                ?? $thumbnails['high']['url']
                ?? '';

            return [
                'videoId' => $item['id'] ?? '',
                'title' => $snippet['title'] ?? 'Untitled',
                'channelName' => $snippet['channelTitle'] ?? 'YouTube',
                'thumbnail' => $thumbnail,
                'duration' => $this->parseDuration($item['contentDetails']['duration'] ?? 'PT0S'),
            ];
        }, $response['items'] ?? []);
    }

    private function parseDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);
            return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function apiKey(): ?string
    {
        $key = $_ENV['YOUTUBE_API_KEY'] ?? $_SERVER['YOUTUBE_API_KEY'] ?? (getenv('YOUTUBE_API_KEY') ?: null);
        return $key ? trim((string) $key) : null;
    }
}
