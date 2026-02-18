<?php

namespace App\Controller;

use App\Entity\Town;
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

final class TownController extends AbstractController
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

    #[Route('/town/{id}',
        name: 'get_town',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getTown(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(Town::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'town_detail' : 'town_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/town', name: 'post_town', methods: ['POST'])]
    public function postTown(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $town = new Town();

        try {
            if (!isset($data['province_id'])) {
                return new JsonResponse($this->doResponse->doErrorResponse('province_id is required'));
            }

            $province = $this->doctrine->getRepository(Province::class)->find($data['province_id']);
            if (!$province) {
                return new JsonResponse($this->doResponse->doErrorResponse('Province not found', 404));
            }
            $town->setProvince($province);
            unset($data['province_id']);

            $town = $this->createMethodsByInput->createMethods($town, $data);

            $errors = $validator->validate($town);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($town);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($town, 'town_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/town/{id}', name: 'put_town', methods: ['PUT'])]
    public function putTown(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $town = $this->doctrine->getRepository(Town::class)->find($id);
        if (!$town) {
            return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
        }

        try {
            if (isset($data['province_id'])) {
                $province = $this->doctrine->getRepository(Province::class)->find($data['province_id']);
                if (!$province) {
                    return new JsonResponse($this->doResponse->doErrorResponse('Province not found', 404));
                }
                $town->setProvince($province);
                unset($data['province_id']);
            }

            $town = $this->createMethodsByInput->createMethods($town, $data);

            $errors = $validator->validate($town);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($town);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($town, 'town_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/town/{id}', name: 'delete_town', methods: ['DELETE'])]
    public function deleteTown(int $id): JsonResponse
    {
        $town = $this->doctrine->getRepository(Town::class)->find($id);
        if (!$town) {
            return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
        }

        $this->doctrine->remove($town);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
