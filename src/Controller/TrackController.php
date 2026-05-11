<?php

namespace App\Controller;

use App\Document\Track;
use App\Repository\TrackRepository;
use App\Repository\CategoryRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tracks')]
class TrackController extends AbstractController
{
    public function __construct(
        private readonly TrackRepository $trackRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ValidatorInterface $validator,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('', name: 'api_tracks_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $categoryId = $request->query->get('category');

        $tracks = $this->trackRepository->findByUserId(
            $user->getId(),
            $categoryId
        );

        return $this->json([
            'tracks' => array_map(fn(Track $t) => $t->toArray(), $tracks),
        ]);
    }

    #[Route('', name: 'api_tracks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        $title = $this->sanitizer->sanitize($data['title'] ?? null);
        $artist = $this->sanitizer->sanitize($data['artist'] ?? null);
        $album = $this->sanitizer->sanitize($data['album'] ?? null);
        $duration = (float) ($data['duration'] ?? 0);
        $categoryId = $data['categoryId'] ?? null;

        $title = $this->sanitizer->enforceMaxLength($title, 300);
        $artist = $this->sanitizer->enforceMaxLength($artist, 300);
        $album = $this->sanitizer->enforceMaxLength($album, 300);

        if (!$title || !$artist) {
            return $this->json(['error' => 'Title and artist are required.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate category ownership if provided
        if ($categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if (!$category || $category->getUserId() !== $user->getId()) {
                return $this->json(['error' => 'Invalid category.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $track = new Track();
        $track->setTitle($title);
        $track->setArtist($artist);
        $track->setAlbum($album);
        $track->setDuration($duration);
        $track->setCategoryId($categoryId);
        $track->setUserId($user->getId());

        $errors = $this->validator->validate($track);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['error' => implode(' ', $messages)], Response::HTTP_BAD_REQUEST);
        }

        $this->trackRepository->save($track);

        return $this->json([
            'message' => 'Track saved.',
            'track' => $track->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_tracks_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $track = $this->trackRepository->find($id);

        if (!$track) {
            return $this->json(['error' => 'Track not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($track->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $track->setTitle($this->sanitizer->enforceMaxLength(
                $this->sanitizer->sanitize($data['title']), 300
            ));
        }
        if (isset($data['artist'])) {
            $track->setArtist($this->sanitizer->enforceMaxLength(
                $this->sanitizer->sanitize($data['artist']), 300
            ));
        }
        if (array_key_exists('album', $data)) {
            $track->setAlbum($this->sanitizer->enforceMaxLength(
                $this->sanitizer->sanitize($data['album']), 300
            ));
        }
        if (isset($data['duration'])) {
            $track->setDuration((float) $data['duration']);
        }

        $this->trackRepository->save($track);

        return $this->json([
            'message' => 'Track updated.',
            'track' => $track->toArray(),
        ]);
    }

    #[Route('/{id}', name: 'api_tracks_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $track = $this->trackRepository->find($id);

        if (!$track) {
            return $this->json(['error' => 'Track not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($track->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $this->trackRepository->remove($track);

        return $this->json(['message' => 'Track deleted.']);
    }

    #[Route('/{id}/category', name: 'api_tracks_assign_category', methods: ['PUT'])]
    public function assignCategory(string $id, Request $request): JsonResponse
    {
        $track = $this->trackRepository->find($id);

        if (!$track) {
            return $this->json(['error' => 'Track not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($track->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $categoryId = $data['categoryId'] ?? null;

        // Allow null to unassign
        if ($categoryId !== null) {
            $category = $this->categoryRepository->find($categoryId);
            if (!$category || $category->getUserId() !== $this->getUser()->getId()) {
                return $this->json(['error' => 'Invalid category.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $track->setCategoryId($categoryId);
        $this->trackRepository->save($track);

        return $this->json([
            'message' => $categoryId ? 'Track assigned to category.' : 'Track removed from category.',
            'track' => $track->toArray(),
        ]);
    }
}
