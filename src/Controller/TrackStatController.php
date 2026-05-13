<?php

namespace App\Controller;

use App\Entity\TrackStat;
use App\Repository\TrackStatRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats')]
class TrackStatController extends AbstractController
{
    public function __construct(
        private readonly TrackStatRepository $trackStatRepository,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('/view', name: 'api_stats_record_view', methods: ['POST'])]
    public function recordView(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $videoId = $this->sanitizer->sanitize($data['videoId'] ?? null);
        if (!$videoId) {
            return $this->json(['error' => 'Video id is required.'], Response::HTTP_BAD_REQUEST);
        }

        $stat = $this->trackStatRepository->findByVideoId($videoId) ?? new TrackStat();
        if (!$stat->getVideoId()) {
            $stat->setVideoId($videoId);
        }
        $stat->recordView((int) ($data['watchTimeSeconds'] ?? 0));
        $this->trackStatRepository->save($stat);

        return $this->json(['message' => 'View recorded.', 'stats' => $stat->toArray()]);
    }

    #[Route('/{videoId}', name: 'api_stats_get', methods: ['GET'])]
    public function get(string $videoId): JsonResponse
    {
        $stat = $this->trackStatRepository->findByVideoId($videoId);
        if (!$stat) {
            return $this->json([
                'videoId' => $videoId,
                'totalViews' => 0,
                'totalWatchTimeSeconds' => 0,
                'totalLikes' => 0,
            ]);
        }

        return $this->json($stat->toArray());
    }
}
