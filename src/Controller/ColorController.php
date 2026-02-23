<?php

namespace App\Controller;

use App\Entity\Color;
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

final class ColorController extends AbstractController
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

    #[Route('/color/{id}',
        name: 'get_color',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getColor(?int $id): JsonResponse
    {
        $colorRepository = $this->doctrine->getRepository(Color::class);

        if ($id) {
            $color = [$colorRepository->find($id)];
            if (!$color[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Color not found', 404));
            }
        } else {
            $color = $colorRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($color, $id ? 'color_detail' : 'color_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/color',
        name: 'post_color',
        methods: ['POST'])]
    public function postColor(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $color = new Color();

        try {
            $color = $this->handleRelations($color, $data);
            $color = $this->createMethodsByInput->createMethods($color, $data);

            $now = new \DateTimeImmutable();
            $color->setCreatedAt($now);
            $color->setUpdatedAt($now);

            $errors = $validator->validate($color);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($color);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($color, 'color_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/color/{id}',
        name: 'put_color',
        methods: ['PUT'])]
    public function modifyColor(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $color = $this->doctrine->getRepository(Color::class)->find($id);

        if (!$color) {
            return new JsonResponse($this->doResponse->doErrorResponse('Color not found', 404));
        }

        try {
            $color = $this->handleRelations($color, $data);
            $color = $this->createMethodsByInput->createMethods($color, $data);
            $color->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($color);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($color);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($color, 'color_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/color/{id}',
        name: 'delete_color',
        methods: ['DELETE'])]
    public function deleteColor(int $id): JsonResponse
    {
        $color = $this->doctrine->getRepository(Color::class)->find($id);
        if (!$color) {
            return new JsonResponse($this->doResponse->doErrorResponse('Color not found', 404));
        }

        $this->doctrine->remove($color);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Color $color, array &$data): Color
    {
        if (isset($data['color_type_id'])) {
            $colorType = $this->doctrine->getRepository(ColorType::class)->find($data['color_type_id']);
            if ($colorType) {
                $color->setColorType($colorType);
            }
            unset($data['color_type_id']);
        }

        return $color;
    }
}
