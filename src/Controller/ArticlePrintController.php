<?php

namespace App\Controller;

use App\Entity\ArticlePrint;
use App\Repository\ArticlePrintRepository;
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
class ArticlePrintController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ArticlePrintRepository   $articlePrintRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/article-prints', name: 'app_article_print_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $prints = $this->articlePrintRepository->findAll();
        $results = $this->groupSerializer->serializeGroup($prints, 'article_print_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-print/{id}', name: 'app_article_print_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $print = $this->articlePrintRepository->find($id);

        if (!$print) {
            return new JsonResponse($this->doResponse->doErrorResponse('Stampa articolo non trovata', status_code: 404));
        }

        $results = $this->groupSerializer->serializeGroup($print, 'article_print_detail');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-print', name: 'app_article_print_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $print = new ArticlePrint();

        try {
            $this->createMethodsByInput->createMethods($print, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($print);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->persist($print);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($print, 'article_print_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-print/{id}', name: 'app_article_print_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $print = $this->articlePrintRepository->find($id);

        if (!$print) {
            return new JsonResponse($this->doResponse->doErrorResponse('Stampa articolo non trovata', status_code: 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->createMethodsByInput->createMethods($print, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($print);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($print, 'article_print_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article-print/{id}', name: 'app_article_print_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $print = $this->articlePrintRepository->find($id);

        if (!$print) {
            return new JsonResponse($this->doResponse->doErrorResponse('Stampa articolo non trovata', status_code: 404));
        }

        $this->entityManager->remove($print);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Stampa articolo eliminata correttamente'));
    }
}
