<?php

namespace App\Controller;

use App\Entity\ArticleClass;
use App\Entity\ArticleType;
use App\Entity\LeatherType;
use App\Repository\ArticleTypeRepository;
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

#[Route('/api')]
class ArticleTypeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ArticleTypeRepository    $articleTypeRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/article-types', name: 'app_article_type_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $articleTypes = $this->articleTypeRepository->findAll();
        $results = $this->groupSerializer->serializeGroup($articleTypes, 'article_type_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-type/{id}', name: 'app_article_type_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $articleType = $this->articleTypeRepository->find($id);

        if (!$articleType) {
            return new JsonResponse($this->doResponse->doErrorResponse('Tipo articolo non trovato', status_code: 404));
        }

        $results = $this->groupSerializer->serializeGroup($articleType, 'article_type_detail');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-type', name: 'app_article_type_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $articleType = new ArticleType();

        try {
            $this->mapDataToEntity($articleType, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($articleType);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->persist($articleType);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($articleType, 'article_type_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-type/{id}', name: 'app_article_type_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $articleType = $this->articleTypeRepository->find($id);

        if (!$articleType) {
            return new JsonResponse($this->doResponse->doErrorResponse('Tipo articolo non trovato', status_code: 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($articleType, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($articleType);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($articleType, 'article_type_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-type/{id}', name: 'app_article_type_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $articleType = $this->articleTypeRepository->find($id);

        if (!$articleType) {
            return new JsonResponse($this->doResponse->doErrorResponse('Tipo articolo non trovato', status_code: 404));
        }

        $this->entityManager->remove($articleType);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Tipo articolo eliminato correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(ArticleType $articleType, array $data): void
    {
        if (isset($data['leather_type_id'])) {
            $leatherType = $this->entityManager->getRepository(LeatherType::class)->find($data['leather_type_id']);
            if (!$leatherType) throw new \Exception("Tipo pelle con ID {$data['leather_type_id']} non trovato");
            $articleType->setLeatherType($leatherType);
            unset($data['leather_type_id']);
        }

        if (isset($data['article_class_id'])) {
            $articleClass = $this->entityManager->getRepository(ArticleClass::class)->find($data['article_class_id']);
            if (!$articleClass) throw new \Exception("Classe articolo con ID {$data['article_class_id']} non trovato");
            $articleType->setArticleClass($articleClass);
            unset($data['article_class_id']);
        }

        $this->createMethodsByInput->createMethods($articleType, $data);
    }
}
