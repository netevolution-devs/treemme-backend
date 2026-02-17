<?php

namespace App\Controller;

use App\Entity\ColorType;
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

final class ColorTypeController extends AbstractController
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

    #[Route('/color-type/{id}',
        name: 'get_color_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getColorType(?int $id): JsonResponse
    {
        $colorTypeRepository = $this->doctrine->getRepository(ColorType::class);

        if ($id) {
            $colorType = [$colorTypeRepository->find($id)];
            if (!$colorType[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ColorType not found', 404));
            }
        } else {
            $colorType = $colorTypeRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($colorType, $id ? 'color_type_detail' : 'color_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/color-type',
        name: 'post_color_type',
        methods: ['POST'])]
    public function postColorType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $colorType = new ColorType();

        try {
            $colorType = $this->createMethodsByInput->createMethods($colorType, $data);

            $now = new \DateTimeImmutable();
            $colorType->setCreatedAt($now);
            $colorType->setUpdatedAt($now);

            $errors = $validator->validate($colorType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($colorType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($colorType, 'color_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/color-type/{id}',
        name: 'put_color_type',
        methods: ['PUT'])]
    public function modifyColorType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $colorType = $this->doctrine->getRepository(ColorType::class)->find($id);

        if (!$colorType) {
            return new JsonResponse($this->doResponse->doErrorResponse('ColorType not found', 404));
        }

        try {
            $colorType = $this->createMethodsByInput->createMethods($colorType, $data);
            $colorType->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($colorType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($colorType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($colorType, 'color_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/color-type/{id}',
        name: 'delete_color_type',
        methods: ['DELETE'])]
    public function deleteColorType(int $id): JsonResponse
    {
        $colorType = $this->doctrine->getRepository(ColorType::class)->find($id);
        if (!$colorType) {
            return new JsonResponse($this->doResponse->doErrorResponse('ColorType not found', 404));
        }

        $this->doctrine->remove($colorType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
