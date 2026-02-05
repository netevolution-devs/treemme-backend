<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupUser;
use App\Entity\User;
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

final class GroupUserController extends AbstractController
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

    #[Route('/group-user/{id}',
        name: 'get_group_user',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getGroupUser(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(GroupUser::class);

        if ($id) {
            $item = [$repository->find($id)];
            if (!$item[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('GroupUser not found', 404));
            }
        } else {
            $item = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($item, $id ? 'group_user_detail' : 'group_user_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/group-user',
        name: 'post_group_user',
        methods: ['POST'])]
    public function postGroupUser(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $item = new GroupUser();

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

            $result = $this->groupSerializer->serializeGroup($item, 'group_user_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/group-user/{id}',
        name: 'put_group_user',
        methods: ['PUT'])]
    public function modifyGroupUser(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $item = $this->doctrine->getRepository(GroupUser::class)->find($id);

        if (!$item) {
            return new JsonResponse($this->doResponse->doErrorResponse('GroupUser not found', 404));
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

            $result = $this->groupSerializer->serializeGroup($item, 'group_user_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/group-user/{id}',
        name: 'delete_group_user',
        methods: ['DELETE'])]
    public function deleteGroupUser(int $id): JsonResponse
    {
        $item = $this->doctrine->getRepository(GroupUser::class)->find($id);
        if (!$item) {
            return new JsonResponse($this->doResponse->doErrorResponse('GroupUser not found', 404));
        }

        $this->doctrine->remove($item);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(GroupUser $item, array &$data): GroupUser
    {
        if (isset($data['group_id'])) {
            $group = $this->doctrine->getRepository(Group::class)->find($data['group_id']);
            if ($group) {
                $item->setGroupp($group);
            }
            unset($data['group_id']);
        }

        if (isset($data['user_id'])) {
            $user = $this->doctrine->getRepository(User::class)->find($data['user_id']);
            if ($user) {
                $item->setUser($user);
            }
            unset($data['user_id']);
        }

        return $item;
    }
}
