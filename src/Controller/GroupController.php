<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupRoleWorkArea;
use App\Entity\GroupUser;
use App\Entity\Role;
use App\Entity\User;
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

final class GroupController extends AbstractController
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

    #[Route('/group/{id}',
        name: 'get_group',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getGroup(
        ?int            $id,
    ): JsonResponse
    {

        $groupRepository = $this->doctrine->getRepository(Group::class);

        if ($id) {
            $group = [$groupRepository->find($id)];
            if (!$group[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('WorkArea not found', '404'));
            }
        } else {
            $group = $groupRepository->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($group, 'list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        } else {
            return new JsonResponse($this->doResponse->doResponse($results));
        }
    }

    #[Route('/group',
        name: 'post_group',
        methods: ['POST'])]
    public function AddGroup(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();

        $group = new Group();

        try {

            $group = $this->createMethodsByInput->createMethods($group, $data);

            $now = new \DateTimeImmutable();

            $group->setCreatedAt($now);
            $group->setUpdatedAt($now);

            $errors = $validator->validate($group);

            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);

                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($group);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($group, 'detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }
    #[Route('/group/{id}',
        name: 'put_group',
        methods: ['PUT'])]
    public function modifyGroup(
        Request            $request,
        ValidatorInterface $validator,
        int                $id
    ): JsonResponse
    {
        $data = $request->toArray();

        $group = $this->doctrine->getRepository(Group::class)->find($id);

        if (!$group) {
            return new JsonResponse($this->doResponse->doErrorResponse('Group not found', 404));
        }

        $group = $this->createMethodsByInput->createMethods($group, $data);

        $now = new \DateTimeImmutable();

        $group->setUpdatedAt($now);

        $errors = $validator->validate($group);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }

        $em = $this->doctrine;
        $em->persist($group);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($group, 'detail');

        return new JsonResponse($this->doResponse->doResponse($result));
    }
    #[Route('/group/{id}',
        name: 'delete_group',
        methods: ['DELETE'])]
    public function deleteGroup(
        int $id
    ): JsonResponse
    {
        $group = $this->doctrine->getRepository(Group::class)->find($id);

        if (!$group) {
            return new JsonResponse($this->doResponse->doErrorResponse('Group not found', 404));
        }

        $em = $this->doctrine;
        $em->remove($group);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    #[Route('/group/add-user',
        name: 'group_add_user',
        methods: ['POST'])]
    public function addUserToGroup(
        Request $request
    ): JsonResponse
    {
        $data = $request->toArray();

        $groupId = $data['group_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        if (!$groupId || !$userId) {
            return new JsonResponse($this->doResponse->doErrorResponse('Missing group_id or user_id', 400));
        }

        $group = $this->doctrine->getRepository(Group::class)->find($groupId);
        $user = $this->doctrine->getRepository(User::class)->find($userId);

        if (!$group || !$user) {
            return new JsonResponse($this->doResponse->doErrorResponse('Group or User not found', 404));
        }

        // Verifica se l'utente è già nel gruppo
        $existing = $this->doctrine->getRepository(GroupUser::class)->findOneBy([
            'groupp' => $group,
            'user' => $user
        ]);

        if ($existing) {
            return new JsonResponse($this->doResponse->doErrorResponse('User already in this group', 400));
        }

        $groupUser = new GroupUser();
        $groupUser->setGroupp($group);
        $groupUser->setUser($user);
        $now = new \DateTimeImmutable();
        $groupUser->setCreatedAt($now);
        $groupUser->setUpdatedAt($now);

        $em = $this->doctrine;
        $em->persist($groupUser);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('User added to group successfully'));
    }

    #[Route('/group/assign-role',
        name: 'group_assign_role',
        methods: ['POST'])]
    public function assignRoleToGroupWorkArea(
        Request $request
    ): JsonResponse
    {
        $data = $request->toArray();

        $groupId = $data['group_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        $workAreaId = $data['work_area_id'] ?? null;

        if (!$groupId || !$roleId || !$workAreaId) {
            return new JsonResponse($this->doResponse->doErrorResponse('Missing group_id, role_id or work_area_id', 400));
        }

        $group = $this->doctrine->getRepository(Group::class)->find($groupId);
        $role = $this->doctrine->getRepository(Role::class)->find($roleId);
        $workArea = $this->doctrine->getRepository(WorkArea::class)->find($workAreaId);

        if (!$group || !$role || !$workArea) {
            return new JsonResponse($this->doResponse->doErrorResponse('Group, Role or WorkArea not found', 404));
        }

        // Verifica se il ruolo è già assegnato a questo gruppo per questa work_area
        $existing = $this->doctrine->getRepository(GroupRoleWorkArea::class)->findOneBy([
            'groupp' => $group,
            'role' => $role,
            'workArea' => $workArea
        ]);

        if ($existing) {
            return new JsonResponse($this->doResponse->doErrorResponse('Role already assigned to this group in this work area', 400));
        }

        $groupRoleWorkArea = new GroupRoleWorkArea();
        $groupRoleWorkArea->setGroupp($group);
        $groupRoleWorkArea->setRole($role);
        $groupRoleWorkArea->setWorkArea($workArea);
        $now = new \DateTimeImmutable();
        $groupRoleWorkArea->setCreatedAt($now);
        $groupRoleWorkArea->setUpdatedAt($now);

        $em = $this->doctrine;
        $em->persist($groupRoleWorkArea);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('Role assigned to group in work area successfully'));
    }
}
