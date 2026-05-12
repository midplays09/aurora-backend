<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\TrackRepository;
use App\Security\InputSanitizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly TrackRepository $trackRepository,
        private readonly ValidatorInterface $validator,
        private readonly InputSanitizer $sanitizer,
    ) {}

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $categories = $this->categoryRepository->findByUserId($user->getId());

        return $this->json([
            'categories' => array_map(fn(Category $c) => $c->toArray(), $categories),
        ]);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $name = $this->sanitizer->sanitize($data['name'] ?? null);
        $name = $this->sanitizer->enforceMaxLength($name, 100);

        if (!$name) {
            return $this->json(['error' => 'Category name is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        $category = new Category();
        $category->setName($name);
        $category->setUserId($user->getId());

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['error' => implode(' ', $messages)], Response::HTTP_BAD_REQUEST);
        }

        $this->categoryRepository->save($category);

        return $this->json([
            'message' => 'Category created.',
            'category' => $category->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found.'], Response::HTTP_NOT_FOUND);
        }

        // Owner check — users can only modify their own categories
        if ($category->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $name = $this->sanitizer->sanitize($data['name'] ?? null);
        $name = $this->sanitizer->enforceMaxLength($name, 100);

        if (!$name) {
            return $this->json(['error' => 'Category name is required.'], Response::HTTP_BAD_REQUEST);
        }

        $category->setName($name);
        $this->categoryRepository->save($category);

        return $this->json([
            'message' => 'Category updated.',
            'category' => $category->toArray(),
        ]);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found.'], Response::HTTP_NOT_FOUND);
        }

        // Owner check
        if ($category->getUserId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        // Unset category on all tracks that belong to this category
        $this->trackRepository->unsetCategoryForAll($id);

        $this->categoryRepository->remove($category);

        return $this->json(['message' => 'Category deleted.']);
    }
}
