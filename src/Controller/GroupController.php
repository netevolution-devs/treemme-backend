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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'group')]
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
                return $this->doResponse->doErrorJsonResponse('WorkArea not found', '404');
            }
        } else {
            $group = $groupRepository->findBy([], ['name' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($group, $id ? 'group_detail' : 'group_list');

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

                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($group);

            $workAreas = $em->getRepository(WorkArea::class)->findAll();
            $role = $em->getRepository(Role::class)->findOneBy(['name' => 'USER']) ?? $em->getRepository(Role::class)->findOneBy([]);

            if ($role) {
                foreach ($workAreas as $workArea) {
                    $groupRoleWorkArea = new GroupRoleWorkArea();
                    $groupRoleWorkArea->setGroup($group);
                    $groupRoleWorkArea->setWorkArea($workArea);
                    $groupRoleWorkArea->setRole($role);
                    $groupRoleWorkArea->setCanGet(true);
                    $groupRoleWorkArea->setCreatedAt($now);
                    $groupRoleWorkArea->setUpdatedAt($now);
                    $em->persist($groupRoleWorkArea);
                }
            }

            $em->flush();

            $result = $this->groupSerializer->serializeGroup($group, 'group_detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
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
            return $this->doResponse->doErrorJsonResponse('Group not found', 404);
        }

        $group = $this->createMethodsByInput->createMethods($group, $data);

        $now = new \DateTimeImmutable();

        $group->setUpdatedAt($now);

        $errors = $validator->validate($group);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return $this->doResponse->doErrorJsonResponse($errors);
        }

        $em = $this->doctrine;
        $em->persist($group);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($group, 'group_detail');

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
            return $this->doResponse->doErrorJsonResponse('Group not found', 404);
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
            return $this->doResponse->doErrorJsonResponse('Missing group_id or user_id', 400);
        }

        $group = $this->doctrine->getRepository(Group::class)->find($groupId);
        $user = $this->doctrine->getRepository(User::class)->find($userId);

        if (!$group || !$user) {
            return $this->doResponse->doErrorJsonResponse('Group or User not found', 404);
        }

        // Verifica se l'utente è già nel gruppo
        $existing = $this->doctrine->getRepository(GroupUser::class)->findOneBy([
            'group' => $group,
            'user' => $user
        ]);

        if ($existing) {
            return $this->doResponse->doErrorJsonResponse('User already in this group', 400);
        }

        $groupUser = new GroupUser();
        $groupUser->setGroup($group);
        $groupUser->setUser($user);
        $now = new \DateTimeImmutable();
        $groupUser->setCreatedAt($now);
        $groupUser->setUpdatedAt($now);

        $em = $this->doctrine;
        $em->persist($groupUser);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('User added to group successfully'));
    }

    #[Route('/api/user/assign-group',
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
            return $this->doResponse->doErrorJsonResponse('Missing group_id, role_id or work_area_id', 400);
        }

        $group = $this->doctrine->getRepository(Group::class)->find($groupId);
        $role = $this->doctrine->getRepository(Role::class)->find($roleId);
        $workArea = $this->doctrine->getRepository(WorkArea::class)->find($workAreaId);

        if (!$group || !$role || !$workArea) {
            return $this->doResponse->doErrorJsonResponse('Group, Role or WorkArea not found', 404);
        }

        // Verifica se il ruolo è già assegnato a questo gruppo per questa work_area
        $existing = $this->doctrine->getRepository(GroupRoleWorkArea::class)->findOneBy([
            'group' => $group,
            'role' => $role,
            'workArea' => $workArea
        ]);

        if ($existing) {
            return $this->doResponse->doErrorJsonResponse('Role already assigned to this group in this work area', 400);
        }

        $groupRoleWorkArea = new GroupRoleWorkArea();
        $groupRoleWorkArea->setGroup($group);
        $groupRoleWorkArea->setRole($role);
        $groupRoleWorkArea->setWorkArea($workArea);
        $now = new \DateTimeImmutable();
        $groupRoleWorkArea->setCreatedAt($now);
        $groupRoleWorkArea->setUpdatedAt($now);

        $groupRoleWorkArea->setCanGet((bool)($data['can_get'] ?? false));
        $groupRoleWorkArea->setCanPost((bool)($data['can_post'] ?? false));
        $groupRoleWorkArea->setCanPut((bool)($data['can_put'] ?? false));
        $groupRoleWorkArea->setCanDelete((bool)($data['can_delete'] ?? false));

        $groupRoleWorkArea->setCheckOrder((int)($data['check_order'] ?? false));

        $em = $this->doctrine;
        $em->persist($groupRoleWorkArea);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('Role assigned to group in work area successfully'));
    }

    #[Route('/api/user/remove-group/{id}',
        name: 'group_remove_role',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function removeRoleToGroupWorkArea( int $id): JsonResponse
    {
        $GroupRoleWorkArea = $this->doctrine->getRepository(GroupRoleWorkArea::class)->find($id);
        if (!$GroupRoleWorkArea) {
            return $this->doResponse->doErrorJsonResponse('GroupRoleWorkArea not found', 404);
        }

        $em = $this->doctrine;
        $em->remove($GroupRoleWorkArea);
        $em->flush();
        return new JsonResponse($this->doResponse->doResponse('Role removed from group in work area successfully'));
    }

    #[Route('/api/user/assign-user',
        name: 'user_assign_group',
        methods: ['POST'])]
    public function assignUserToGroup(
        Request $request
    ): JsonResponse
    {
        $data = $request->toArray();

        $userId = $data['user_id'] ?? null;
        $groupId = $data['group_id'] ?? null;

        if (!$userId || !$groupId) {
            return $this->doResponse->doErrorJsonResponse('Missing user_id or group_id', 400);
        }

        $user = $this->doctrine->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->doResponse->doErrorJsonResponse('User not found', 404);
        }

        $group = $this->doctrine->getRepository(Group::class)->find($groupId);

        if (!$group) {
            return $this->doResponse->doErrorJsonResponse('Group not found', 404);
        }

        $GroupUser = new GroupUser();

        $GroupUser->setUser($user);
        $GroupUser->setGroup($group);
        $now = new \DateTimeImmutable();
        $GroupUser->setCreatedAt($now);
        $GroupUser->setUpdatedAt($now);

        $em = $this->doctrine;
        $em->persist($GroupUser);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('Role assigned to user successfully'));
    }

    #[Route('/api/user/remove-user/{id}/{id_group}',
        name: 'user_remove_group',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function removeUserToGroup(
        Request $request,
        int $id,
        int $id_group
    ): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);
        $group = $this->doctrine->getRepository(Group::class)->find($id_group);

        $GroupUser = $this->doctrine->getRepository(GroupUser::class)->findOneBy(['user' => $user, 'group' => $group]);
        if (!$GroupUser) {
            return $this->doResponse->doErrorJsonResponse('GroupUser not found', 404);
        }

        $em = $this->doctrine;
        $em->remove($GroupUser);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('User removed from group successfully'));
    }
}

