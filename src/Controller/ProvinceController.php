<?php

namespace App\Controller;

use App\Entity\Province;
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

final class ProvinceController extends AbstractController
{
    private CreateMethodsByInput $createMethodsByInput;
    private EntityManagerInterface $doctrine;
    private DoResponseService $doResponse;
    private GroupSerializerService $groupSerializer;
    private ValidatorOutputFormatter $validatorOutputFormatter;

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

    #[Route('/province/{id}',
        name: 'get_province',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getProvince(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(Province::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Province not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'province_detail' : 'province_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/province', name: 'post_province', methods: ['POST'])]
    public function postProvince(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $province = new Province();

        try {
            $province = $this->createMethodsByInput->createMethods($province, $data);

            $errors = $validator->validate($province);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($province);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($province, 'province_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/province/{id}', name: 'put_province', methods: ['PUT'])]
    public function putProvince(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $province = $this->doctrine->getRepository(Province::class)->find($id);
        if (!$province) {
            return new JsonResponse($this->doResponse->doErrorResponse('Province not found', 404));
        }

        try {
            $province = $this->createMethodsByInput->createMethods($province, $data);

            $errors = $validator->validate($province);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($province);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($province, 'province_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/province/{id}', name: 'delete_province', methods: ['DELETE'])]
    public function deleteProvince(int $id): JsonResponse
    {
        $province = $this->doctrine->getRepository(Province::class)->find($id);
        if (!$province) {
            return new JsonResponse($this->doResponse->doErrorResponse('Province not found', 404));
        }

        $this->doctrine->remove($province);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
