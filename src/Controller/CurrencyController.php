<?php

namespace App\Controller;

use App\Entity\Currency;
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

final class CurrencyController extends AbstractController
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

    #[Route('/currency/{id}',
        name: 'get_currency',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getCurrency(?int $id): JsonResponse
    {
        $currencyRepository = $this->doctrine->getRepository(Currency::class);

        if ($id) {
            $currency = [$currencyRepository->find($id)];
            if (!$currency[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Currency not found', 404));
            }
        } else {
            $currency = $currencyRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($currency, $id ? 'currency_detail' : 'currency_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/currency',
        name: 'post_currency',
        methods: ['POST'])]
    public function postCurrency(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $currency = new Currency();

        try {
            $currency = $this->createMethodsByInput->createMethods($currency, $data);

            $errors = $validator->validate($currency);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($currency);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($currency, 'currency_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/currency/{id}',
        name: 'put_currency',
        methods: ['PUT'])]
    public function modifyCurrency(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $currency = $this->doctrine->getRepository(Currency::class)->find($id);

        if (!$currency) {
            return new JsonResponse($this->doResponse->doErrorResponse('Currency not found', 404));
        }

        try {
            $currency = $this->createMethodsByInput->createMethods($currency, $data);

            $errors = $validator->validate($currency);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($currency);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($currency, 'currency_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/currency/{id}',
        name: 'delete_currency',
        methods: ['DELETE'])]
    public function deleteCurrency(int $id): JsonResponse
    {
        $currency = $this->doctrine->getRepository(Currency::class)->find($id);
        if (!$currency) {
            return new JsonResponse($this->doResponse->doErrorResponse('Currency not found', 404));
        }

        $this->doctrine->remove($currency);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
