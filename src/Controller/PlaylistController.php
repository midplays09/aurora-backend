<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Repository\PlaylistRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/playlists')]
class PlaylistController extends AbstractController
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('', name: 'api_playlists_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $playlists = $this->playlistRepository->findByUserId($user->getId());

        return $this->json(['playlists' => array_map(fn(Playlist $p) => $p->toArray(), $playlists)]);
    }

    #[Route('', name: 'api_playlists_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $name = $this->sanitizer->enforceMaxLength($this->sanitizer->sanitize($data['name'] ?? null), 100);
        if (!$name) {
            return $this->json(['error' => 'Playlist name is required.'], Response::HTTP_BAD_REQUEST);
        }

        $playlist = new Playlist();
        $playlist->setName($name);
        $playlist->setUserId($this->getUser()->getId());
        $this->playlistRepository->save($playlist);

        return $this->json(['message' => 'Playlist created.', 'playlist' => $playlist->toArray()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_playlists_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $playlist = $this->ownedPlaylist($id);
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $name = $this->sanitizer->enforceMaxLength($this->sanitizer->sanitize($data['name']), 100);
            if ($name) {
                $playlist->setName($name);
            }
        }
        if (isset($data['trackIds']) && is_array($data['trackIds'])) {
            $playlist->setTrackIds(array_values(array_unique(array_filter($data['trackIds'], 'is_string'))));
        }

        $this->playlistRepository->save($playlist);
        return $this->json(['message' => 'Playlist updated.', 'playlist' => $playlist->toArray()]);
    }

    #[Route('/{id}', name: 'api_playlists_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $playlist = $this->ownedPlaylist($id);
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->playlistRepository->remove($playlist);
        return $this->json(['message' => 'Playlist deleted.']);
    }

    #[Route('/{id}/tracks', name: 'api_playlists_add_track', methods: ['POST'])]
    public function addTrack(string $id, Request $request): JsonResponse
    {
        $playlist = $this->ownedPlaylist($id);
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $videoId = $this->sanitizer->sanitize($data['videoId'] ?? null);
        if (!$videoId) {
            return $this->json(['error' => 'Video id is required.'], Response::HTTP_BAD_REQUEST);
        }

        $playlist->addTrackId($videoId);
        $this->playlistRepository->save($playlist);
        return $this->json(['message' => 'Track added.', 'playlist' => $playlist->toArray()]);
    }

    #[Route('/{id}/tracks/{videoId}', name: 'api_playlists_remove_track', methods: ['DELETE'])]
    public function removeTrack(string $id, string $videoId): JsonResponse
    {
        $playlist = $this->ownedPlaylist($id);
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found.'], Response::HTTP_NOT_FOUND);
        }

        $playlist->removeTrackId($videoId);
        $this->playlistRepository->save($playlist);
        return $this->json(['message' => 'Track removed.', 'playlist' => $playlist->toArray()]);
    }

    private function ownedPlaylist(string $id): ?Playlist
    {
        $playlist = $this->playlistRepository->find($id);
        if (!$playlist || $playlist->getUserId() !== $this->getUser()->getId()) {
            return null;
        }

        return $playlist;
    }
}
