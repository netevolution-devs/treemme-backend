<?php

namespace App\Controller;

use App\Entity\WorkArea;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorkAreaController extends AbstractController
{
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

    #[Route('/work/area/{id}',
        name: 'get_work_area',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getWorkArea(
        ?int            $id,
    ): JsonResponse
    {

        $workAreaRepository = $this->doctrine
            ->getRepository(WorkArea::class);
        
        if ($id) {
            $workArea = [$workAreaRepository->find($id)];
            if (!$workArea[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('WorkArea not found', '404'));
            }
        } else {
            $workArea = $workAreaRepository->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($workArea, 'list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        } else {
            return new JsonResponse($this->doResponse->doResponse($results));
        }
    }

    #[Route('/backoffice/work/area',
        name: 'post_work_area',
        methods: ['POST'])]
    public function AddWorkArea(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();

        $workArea = new WorkArea();

        try {

            $workArea = $this->createMethodsByInput->createMethods($workArea, $data);

            $now = new \DateTimeImmutable();

            $workArea->setCreatedAt($now);
            $workArea->setUpdatedAt($now);

            $errors = $validator->validate($workArea);

            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);

                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($workArea);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($workArea, 'detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }
    #[Route('/backoffice/work/area/{id}',
        name: 'put_work_area',
        methods: ['PUT'])]
    public function modifyWorkArea(
        Request            $request,
        ValidatorInterface $validator,
        int                $id
    ): JsonResponse
    {
        $data = $request->toArray();

        $workArea = $this->doctrine->getRepository(WorkArea::class)->find($id);

        if (!$workArea) {
            return new JsonResponse($this->doResponse->doErrorResponse('WorkArea not found', 404));
        }

        $workArea = $this->createMethodsByInput->createMethods($workArea, $data);

        $now = new \DateTimeImmutable();

        $workArea->setUpdatedAt($now);

        $errors = $validator->validate($workArea);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }

        $em = $this->doctrine;
        $em->persist($workArea);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($workArea, 'detail');

        return new JsonResponse($this->doResponse->doResponse($result));
    }
    #[Route('/backoffice/work/area/{id}',
        name: 'delete_work_area',
        methods: ['DELETE'])]
    public function deleteWorkArea(
        int $id
    ): JsonResponse
    {
        $workArea = $this->doctrine->getRepository(WorkArea::class)->find($id);

        if (!$workArea) {
            return new JsonResponse($this->doResponse->doErrorResponse('WorkArea not found', 404));
        }

        $em = $this->doctrine;
        $em->remove($workArea);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
