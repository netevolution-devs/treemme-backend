<?php

namespace App\Controller;

use App\Entity\Role;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleController extends AbstractController
{
    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        TranslatorInterface      $translator,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->translator = $translator;
    }

    #[Route('/role/{id}',
        name: 'get_role',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getRole(
        ?int        $id,
        Request     $request,
    ): JsonResponse
    {

        $type = $request->query->get('type');

        $roleRepository = $this->doctrine->getRepository(Role::class);

        if ($id) {
            $roles = [$roleRepository->find($id)];
            if (!$roles[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse($this->translator->trans('entity.error.not_found', ['%entity%' => 'Role'], 'messages')), 404);
            }
        } else {
            $roles = $roleRepository->findBy([], ['id' => 'DESC']);

            if (strtolower((string) $type) === 'backoffice') {
                $roles = array_values(array_filter($roles, static function (Role $role): bool {
                    $name = $role->getName();
                    return $name !== null && stripos($name, 'backoffice') !== false;
                }));
            }
        }

        $results = $this->groupSerializer->serializeGroup($roles, $id ? 'role_detail' : 'role_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        } else {
            return new JsonResponse($this->doResponse->doResponse($results));
        }
    }

    #[Route('/backoffice/role', name: 'post_role', methods: ['POST'])]
    public function addRole(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();

        $role = new Role();

        try {
            $role = $this->createMethodsByInput->createMethods($role, $data);

            $now = new \DateTimeImmutable();
            // Set timestamps if available on entity
            if (method_exists($role, 'setCreatedAt')) {
                $role->setCreatedAt($now);
            }
            if (method_exists($role, 'setUpdatedAt')) {
                $role->setUpdatedAt($now);
            }

            $errors = $validator->validate($role);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($role);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($role, 'detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/backoffice/role/{id}', name: 'put_role', methods: ['PUT'])]
    public function modifyRole(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $role = $this->doctrine->getRepository(Role::class)->find($id);
        if (!$role) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->translator->trans('entity.error.not_found', ['%entity%' => 'Role'], 'messages')), 404);
        }

        $role = $this->createMethodsByInput->createMethods($role, $data);

        $now = new \DateTimeImmutable();
        if (method_exists($role, 'setUpdatedAt')) {
            $role->setUpdatedAt($now);
        }

        $errors = $validator->validate($role);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }

        $em = $this->doctrine;
        $em->persist($role);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($role, 'detail');
        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/backoffice/role/{id}', name: 'delete_role', methods: ['DELETE'])]
    public function deleteRole(int $id): JsonResponse
    {
        $role = $this->doctrine->getRepository(Role::class)->find($id);
        if (!$role) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->translator->trans('entity.error.not_found', ['%entity%' => 'Role'], 'messages')), 404);
        }

        $em = $this->doctrine;
        $em->remove($role);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
