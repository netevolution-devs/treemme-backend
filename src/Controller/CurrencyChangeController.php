<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\CurrencyChange;
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

final class CurrencyChangeController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
    }

    #[Route('/currency-change/{id}',
        name: 'get_currency_change',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getCurrencyChange(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(CurrencyChange::class);

        if ($id) {
            $currencyChange = $repository->find($id);
            if (!$currencyChange) {
                return new JsonResponse($this->doResponse->doErrorResponse('Currency change not found', 404));
            }
            $results = [$currencyChange];
        } else {
            $results = $repository->findBy([], ['id' => 'DESC']);
        }

        $serialized = $this->groupSerializer->serializeGroup($results, $id ? 'currency_change_detail' : 'currency_change_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($serialized[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($serialized));
    }

    #[Route('/currency-change',
        name: 'post_currency_change',
        methods: ['POST'])]
    public function postCurrencyChange(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }
        
        $currencyChange = new CurrencyChange();

        try {
            $currencyChange = $this->handleRelations($currencyChange, $data);
            $currencyChange = $this->createMethodsByInput->createMethods($currencyChange, $data);

            $errors = $validator->validate($currencyChange);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($currencyChange);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($currencyChange, 'currency_change_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/currency-change/{id}',
        name: 'put_currency_change',
        methods: ['PUT', 'PATCH'])]
    public function putCurrencyChange(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $currencyChange = $this->doctrine->getRepository(CurrencyChange::class)->find($id);

        if (!$currencyChange) {
            return new JsonResponse($this->doResponse->doErrorResponse('Currency change not found', 404));
        }

        try {
            $currencyChange = $this->handleRelations($currencyChange, $data);
            $currencyChange = $this->createMethodsByInput->createMethods($currencyChange, $data);

            $errors = $validator->validate($currencyChange);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($currencyChange, 'currency_change_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/currency-change/{id}',
        name: 'delete_currency_change',
        methods: ['DELETE'])]
    public function deleteCurrencyChange(int $id): JsonResponse
    {
        $currencyChange = $this->doctrine->getRepository(CurrencyChange::class)->find($id);
        if (!$currencyChange) {
            return new JsonResponse($this->doResponse->doErrorResponse('Currency change not found', 404));
        }

        $this->doctrine->remove($currencyChange);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(CurrencyChange $currencyChange, array &$data): CurrencyChange
    {
        if (isset($data['currency'])) {
            $currency = $this->doctrine->getRepository(Currency::class)->find($data['currency']);
            if (!$currency) {
                throw new \RuntimeException('Currency not found');
            }
            $currencyChange->setCurrency($currency);
            unset($data['currency']);
        }

        return $currencyChange;
    }
}
