<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticlePrint;
use App\Entity\ArticleType;
use App\Entity\Color;
use App\Entity\Contact;
use App\Entity\LeatherThickness;
use App\Entity\Product;
use App\Repository\ArticleRepository;
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

class ArticleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ArticleRepository        $articleRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/article/{id}', name: 'app_article_index', methods: ['GET'], defaults: ['id' => null], requirements: ['id' => '\d+'])]
    public function index(Request $request, ?int $id = null): JsonResponse
    {
        if ($id) {
            $article = $this->articleRepository->find($id);

            if (!$article) {
                return $this->doResponse->doErrorJsonResponse('Articolo non trovato', status_code: 404);
            }

            $results = $this->groupSerializer->serializeGroup($article, 'article_detail');

            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $clientId = $request->query->get('client');

        if ($clientId) {
            $client = $this->entityManager->getRepository(Contact::class)->find($clientId);

            if (!$client) {
                return $this->doResponse->doErrorJsonResponse('Cliente non trovato', 404);
            }

            $articles = $this->articleRepository->findBy(['client' => $client], ['name' => 'ASC']);
        } else {
            $articles = $this->articleRepository->findBy([], ['name' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($articles, 'article_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article', name: 'app_article_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $article = new Article();

        try {
            $this->mapDataToEntity($article, $data);

            if (!$article->getCode()) {
                $prefix = 'AR';
                $lastCode = $this->articleRepository->findLatestArticleCode($prefix);

                $nextNumber = 1;
                if ($lastCode && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $lastCode, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }
                
                $article->setCode($prefix . str_pad((string)$nextNumber, 6, '0', STR_PAD_LEFT));
            }
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }

        $errors = $validator->validate($article);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return $this->doResponse->doErrorJsonResponse($formattedErrors);
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($article, 'article_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article/{id}', name: 'app_article_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $article = $this->articleRepository->find($id);

        if (!$article) {
            return $this->doResponse->doErrorJsonResponse('Articolo non trovato', status_code: 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($article, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }

        $errors = $validator->validate($article);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return $this->doResponse->doErrorJsonResponse($formattedErrors);
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($article, 'article_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/article/{id}', name: 'app_article_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);

        if (!$article) {
            return $this->doResponse->doErrorJsonResponse('Articolo non trovato', status_code: 404);
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Articolo eliminato correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(Article $article, array $data): void
    {
        // Relazioni
        if (isset($data['client_id'])) {
            $client = $this->entityManager->getRepository(Contact::class)->find($data['client_id']);
            if (!$client) throw new \Exception("Cliente con ID {$data['client_id']} non trovato");
            $article->setClient($client);
            unset($data['client_id']);
        }

        if (isset($data['article_type_id'])) {
            $type = $this->entityManager->getRepository(ArticleType::class)->find($data['article_type_id']);
            if (!$type) throw new \Exception("Tipo articolo con ID {$data['article_type_id']} non trovato");
            $article->setArticleType($type);
            unset($data['article_type_id']);
        }

        if (isset($data['thickness_id'])) {
            $thickness = $this->entityManager->getRepository(LeatherThickness::class)->find($data['thickness_id']);
            if (!$thickness) throw new \Exception("Spessore con ID {$data['thickness_id']} non trovato");
            $article->setThickness($thickness);
            unset($data['thickness_id']);
        }

        if (isset($data['print_id'])) {
            $print = $this->entityManager->getRepository(ArticlePrint::class)->find($data['print_id']);
            if (!$print) throw new \Exception("Stampa con ID {$data['print_id']} non trovato");
            $article->setPrint($print);
            unset($data['print_id']);
        }

        if (isset($data['color_id'])) {
            $color = $this->entityManager->getRepository(Color::class)->find($data['color_id']);
            if (!$color) throw new \Exception("Colore con ID {$data['color_id']} non trovato");
            $article->setColor($color);
            unset($data['color_id']);
        }

        $this->createMethodsByInput->createMethods($article, $data);

        $nameParts = [
            $article->getArticleType()?->getName(),
            $article->getArticleType()?->getLeatherType()?->getName(),
            $article->getThickness()?->getName(),
            $article->getColor()?->getColor(),
        ];

        $article->setName(strtoupper(implode(' ', array_filter(
            $nameParts,
            static fn (?string $value): bool => $value !== null && trim($value) !== ''
        ))));

        $clientCodeParts = [
            $article->getArticleType()?->getName(),
            $article->getThickness()?->getName(),
            $article->getColor()?->getColor(),
        ];

        $article->setClientCode(strtoupper(implode(' ', array_filter(
            $clientCodeParts,
            static fn (?string $value): bool => $value !== null && trim($value) !== ''
        ))));

        $codeParts = [
            $this->compressString($article->getArticleType()?->getName()),
            $article->getThickness()?->getName(),
            $this->compressString($article->getColor()?->getColor()),
            $this->compressString($article->getClient()?->getName()),
        ];

        $article->setCode(strtoupper(implode('-', array_filter(
            $codeParts,
            static fn (?string $value): bool => $value !== null && trim($value) !== ''
        ))));
    }

    private function compressString(?string $string): ?string
    {
        if (!$string) return null;
        
        $string = trim($string);
        if ($string === '') return null;

        if (strlen($string) <= 3) return strtoupper($string);

        $consonants = preg_replace('/[aeiou\s]/i', '', $string);
        if (strlen($consonants) >= 3) {
            return strtoupper(substr($consonants, 0, 3));
        }

        return strtoupper(substr($string, 0, 3));
    }
}


