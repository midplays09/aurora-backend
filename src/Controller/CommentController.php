<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly InputSanitizer $sanitizer,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/{videoId}', name: 'api_comments_list', methods: ['GET'])]
    public function list(string $videoId): JsonResponse
    {
        $comments = $this->commentRepository->findByVideoId($videoId);
        return $this->json(['comments' => array_map(fn(Comment $c) => $c->toArray(), $comments)]);
    }

    #[Route('', name: 'api_comments_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $videoId = $this->sanitizer->sanitize($data['videoId'] ?? null);
        $text = $this->sanitizer->enforceMaxLength($this->sanitizer->sanitize($data['text'] ?? null), 1000);
        if (!$videoId || !$text) {
            return $this->json(['error' => 'Video id and comment text are required.'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setUserId($user->getId());
        $comment->setUserEmail($user->getEmail());
        $comment->setUsername($user->getUsername());
        $comment->setVideoId($videoId);
        $comment->setText($text);

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(['error' => $errors[0]->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->commentRepository->save($comment);
        return $this->json(['message' => 'Comment added.', 'comment' => $comment->toArray()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment || $comment->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Comment not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->commentRepository->remove($comment);
        return $this->json(['message' => 'Comment deleted.']);
    }
}
