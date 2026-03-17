<?php

namespace App\Controller;

use App\Entity\ArticleClass;
use App\Repository\ArticleClassRepository;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleClassController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ArticleClassRepository   $articleClassRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/article-class/{id}', name: 'app_article_class_show', methods: ['GET'], requirements: ['id' => '\d+'], defaults: ['id' => null])]
    public function show(?int $id): JsonResponse
    {
        if ($id === null) {
            $classes = $this->articleClassRepository->findAll();
            $results = $this->groupSerializer->serializeGroup($classes, 'article_class_list');
            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $class = $this->articleClassRepository->find($id);

        if (!$class) {
            return new JsonResponse($this->doResponse->doErrorResponse('Classe articolo non trovata', status_code: 404));
        }

        $results = $this->groupSerializer->serializeGroup($class, 'article_class_detail');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-class', name: 'app_article_class_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $class = new ArticleClass();

        try {
            $this->mapDataToEntity($class, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($class);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->persist($class);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($class, 'article_class_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-class/{id}', name: 'app_article_class_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $class = $this->articleClassRepository->find($id);

        if (!$class) {
            return new JsonResponse($this->doResponse->doErrorResponse('Classe articolo non trovata', status_code: 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($class, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($class);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($class, 'article_class_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-class/{id}', name: 'app_article_class_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $class = $this->articleClassRepository->find($id);

        if (!$class) {
            return new JsonResponse($this->doResponse->doErrorResponse('Classe articolo non trovata', status_code: 404));
        }

        $this->entityManager->remove($class);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Classe articolo eliminata correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(ArticleClass $class, array $data): void
    {
        // Mappatura campi semplici
        $this->createMethodsByInput->createMethods($class, $data);
    }
}
