<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupRoleWorkArea;
use App\Entity\Role;
use App\Entity\WorkArea;
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

final class GroupRoleWorkAreaController extends AbstractController
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

    #[Route('/group-role-work-area/{id}',
        name: 'get_group_role_work_area',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getGroupRoleWorkArea(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(GroupRoleWorkArea::class);

        if ($id) {
            $item = [$repository->find($id)];
            if (!$item[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('GroupRoleWorkArea not found', 404));
            }
        } else {
            $item = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($item, $id ? 'group_role_work_area_detail' : 'group_role_work_area_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/group-role-work-area',
        name: 'post_group_role_work_area',
        methods: ['POST'])]
    public function postGroupRoleWorkArea(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $item = new GroupRoleWorkArea();

        try {
            $item = $this->handleRelations($item, $data);
            $item = $this->createMethodsByInput->createMethods($item, $data);

            $now = new \DateTimeImmutable();
            $item->setCreatedAt($now);
            $item->setUpdatedAt($now);

            $errors = $validator->validate($item);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($item);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($item, 'group_role_work_area_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/group-role-work-area/{id}',
        name: 'put_group_role_work_area',
        methods: ['PUT'])]
    public function modifyGroupRoleWorkArea(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $item = $this->doctrine->getRepository(GroupRoleWorkArea::class)->find($id);

        if (!$item) {
            return new JsonResponse($this->doResponse->doErrorResponse('GroupRoleWorkArea not found', 404));
        }

        try {
            $item = $this->handleRelations($item, $data);
            $item = $this->createMethodsByInput->createMethods($item, $data);
            $item->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($item);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($item);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($item, 'group_role_work_area_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/group-role-work-area/{id}',
        name: 'delete_group_role_work_area',
        methods: ['DELETE'])]
    public function deleteGroupRoleWorkArea(int $id): JsonResponse
    {
        $item = $this->doctrine->getRepository(GroupRoleWorkArea::class)->find($id);
        if (!$item) {
            return new JsonResponse($this->doResponse->doErrorResponse('GroupRoleWorkArea not found', 404));
        }

        $this->doctrine->remove($item);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(GroupRoleWorkArea $item, array &$data): GroupRoleWorkArea
    {
        if (isset($data['group_id'])) {
            $group = $this->doctrine->getRepository(Group::class)->find($data['group_id']);
            if ($group) {
                $item->setGroupp($group);
            }
            unset($data['group_id']);
        }

        if (isset($data['role_id'])) {
            $role = $this->doctrine->getRepository(Role::class)->find($data['role_id']);
            if ($role) {
                $item->setRole($role);
            }
            unset($data['role_id']);
        }

        if (isset($data['work_area_id'])) {
            $workArea = $this->doctrine->getRepository(WorkArea::class)->find($data['work_area_id']);
            if ($workArea) {
                $item->setWorkArea($workArea);
            }
            unset($data['work_area_id']);
        }

        return $item;
    }
}
