<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/favorites')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('', name: 'api_favorites_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $favorites = $this->favoriteRepository->findByUserId($this->getUser()->getId());
        return $this->json(['favorites' => array_map(fn(Favorite $f) => $f->toArray(), $favorites)]);
    }

    #[Route('', name: 'api_favorites_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $this->getUser()->getId();
        $videoId = $this->sanitizer->sanitize($data['videoId'] ?? null);
        if (!$videoId) {
            return $this->json(['error' => 'Video id is required.'], Response::HTTP_BAD_REQUEST);
        }

        $favorite = $this->favoriteRepository->findOneByUserAndVideo($userId, $videoId) ?? new Favorite();
        $favorite->setUserId($userId);
        $favorite->setVideoId($videoId);
        $favorite->setTitle($this->sanitizer->enforceMaxLength($this->sanitizer->sanitize($data['title'] ?? ''), 300) ?: 'Untitled');
        $favorite->setChannelName($this->sanitizer->enforceMaxLength($this->sanitizer->sanitize($data['channelName'] ?? ''), 300) ?: 'Unknown');
        $favorite->setThumbnail($this->sanitizer->sanitize($data['thumbnail'] ?? '') ?: '');
        $favorite->setDuration((float) ($data['duration'] ?? 0));
        $this->favoriteRepository->save($favorite);

        return $this->json(['message' => 'Favorite saved.', 'favorite' => $favorite->toArray()]);
    }

    #[Route('/check/{videoId}', name: 'api_favorites_check', methods: ['GET'])]
    public function check(string $videoId): JsonResponse
    {
        return $this->json([
            'isFavorited' => $this->favoriteRepository->findOneByUserAndVideo($this->getUser()->getId(), $videoId) !== null,
        ]);
    }

    #[Route('/{videoId}', name: 'api_favorites_remove', methods: ['DELETE'])]
    public function remove(string $videoId): JsonResponse
    {
        $favorite = $this->favoriteRepository->findOneByUserAndVideo($this->getUser()->getId(), $videoId);
        if ($favorite) {
            $this->favoriteRepository->remove($favorite);
        }

        return $this->json(['message' => 'Favorite removed.']);
    }
}
