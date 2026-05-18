<?php

namespace App\Controller\Security;

use App\Entity\GroupRoleWorkArea;
use App\Entity\User;
use App\Service\ActionLoggerService;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\UserService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class UserController extends AbstractController
{
    private UserService $userService;
    private RequestStack $request;
    private DoResponseService $doResponse;
    private EntityManagerInterface $doctrine;
    private GroupSerializerService $groupSerializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        DoResponseService $doResponseService,
        RequestStack $request,
        GroupSerializerService $groupSerializer,
        UserService $userService
    )
    {
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->request = $request;
        $this->groupSerializer = $groupSerializer;
        $this->userService = $userService;
    }


    #[Route('/api/user', name: 'get_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $users = $this->doctrine->getRepository(User::class)->findBy([], ['email' => 'ASC']);
        $userData = $this->groupSerializer->serializeGroup($users, 'user_detail');

        return new JsonResponse($this->doResponse->doResponse($userData));
    }

    #[Route('/api/user/{id}', name: 'get_user_detail', methods: ['GET'])]
    public function getUserDetail(int $id): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->doResponse->doErrorJsonResponse('Utente non trovato', 404);
        }

        $userData = $this->groupSerializer->serializeGroup($user, 'user_detail');

        return new JsonResponse($this->doResponse->doResponse($userData));
    }

    #[Route('/api/user', name: 'add_user', methods: ['POST'])]
    public function addUser(
        UserPasswordHasherInterface $passwordHasher,
        CreateMethodsByInput $createMethodsByInput,
        ValidatorInterface $validator,
        ValidatorOutputFormatter $validatorOutputFormatter,
        ActionLoggerService $actionLoggerService,
    ): JsonResponse
    {
        $data = $this->request->getCurrentRequest()->request->all();

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->doResponse->doErrorJsonResponse('Email e password obbligatori', 400);
        }

        $role = $data['role'] ?? 'ROLE_USER';
        $roles = explode(',', $role);

        $user = new User();

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $password
        );

        $code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 9);

        $user->setEmail($email)
            ->setPassword($hashedPassword)
            ->setRoles($roles)
            ->setUserCode($code);

        unset($data['password']);

        $createMethodsByInput->createMethods($user, $data);

        $errors = $validator->validate($user);

        if (count($errors) > 0) {

            return new JsonResponse(array('errors' => $validatorOutputFormatter->formatOutput($errors)), 400);
        }

        $this->doctrine->persist($user);
        try {
            $this->doctrine->flush();
        } catch (Exception $e) {

            return $this->doResponse->doErrorJsonResponse('indirizzo email già esistente',$e->getFile());
        }

        $actionLoggerService->logAction('add_new_user', $this->groupSerializer->serializeGroup($user, 'user_detail'));

        return new JsonResponse($this->doResponse->doResponse(['id' => $user->getId()]));
    }

    #[Route('/api/user/{id}', name: 'edit_user', methods: ['PUT'])]
    public function editUser(
        int $id,
        UserPasswordHasherInterface $passwordHasher,
        CreateMethodsByInput $createMethodsByInput,
        ValidatorInterface $validator,
        ValidatorOutputFormatter $validatorOutputFormatter,
        ActionLoggerService $actionLoggerService,
    ): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->doResponse->doErrorJsonResponse('Utente non trovato', 404);
        }

        $data = $this->request->getCurrentRequest()->toArray();

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $roles = explode(',', $data['role']);
            $user->setRoles($roles);
            unset($data['role']);
        }

        try {
            $createMethodsByInput->createMethods($user, $data);
        } catch (Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new JsonResponse(array('errors' => $validatorOutputFormatter->formatOutput($errors)), 400);
        }

        try {
            $this->doctrine->persist($user);
            $this->doctrine->flush();
        } catch (Exception $e) {
            return $this->doResponse->doErrorJsonResponse('Errore durante il salvataggio o email già esistente', $e->getFile());
        }

        $actionLoggerService->logAction('edit_user', $this->groupSerializer->serializeGroup($user, 'user_detail'));

        return new JsonResponse($this->doResponse->doResponse(['id' => $user->getId()]));
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Delete a User",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="string", example="ok")
     *     )
     * )
     *
     * @OA\Tag(name="delete_user")
     * @Security(name="Bearer")
     *
     */
    #[Route('/api/user/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(
        int $id,
        ActionLoggerService $actionLoggerService,
    ): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->doResponse->doErrorJsonResponse('Utente non trovato', 404);
        }

        $userData = $this->groupSerializer->serializeGroup($user, 'user_detail');

        $this->doctrine->remove($user);
        try {
            $this->doctrine->flush();
        } catch (Exception $e) {
            return $this->doResponse->doErrorJsonResponse('Errore durante la cancellazione', $e->getFile());
        }

        $actionLoggerService->logAction('delete_user', $userData);

        return new JsonResponse($this->doResponse->doResponse(['status' => 'deleted']));
    }

    #[Route('/api/whoami', name: 'whoami', methods: ['GET'])]
    public function whoami(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        $groupUsers = $user->getGroupUsers();
        $roles = [];
        $accessControl = [];

        foreach ($groupUsers as $groupUser) {
            $group = $groupUser->getGroup();
            if ($group) {
                $groupRoleWorkAreas = $this->doctrine->getRepository(GroupRoleWorkArea::class)->findBy(['group' => $group]);
                foreach ($groupRoleWorkAreas as $grwa) {
                    $role = $grwa->getRole();
                    $workArea = $grwa->getWorkArea();
                    
                    if ($role) {
                        $roles[] = $role->getName();
                    }

                    $accessControl[] = [
                        'group' => $group->getName(),
                        'role' => $role ? $role->getName() : null,
                        'work_area' => $workArea ? $workArea->getName() : null,
                        'can_get' => $grwa->isCanGet(),
                        'can_post' => $grwa->isCanPost(),
                        'can_put' => $grwa->isCanPut(),
                        'can_delete' => $grwa->isCanDelete(),
                        'check_order' => $grwa->isCheckOrder(),
                    ];
                }
            }
        }

        $user->setRoles(array_unique($roles));
        
        $userData = $this->groupSerializer->serializeGroup($user, 'user_detail');
        $userData['access_control'] = $accessControl;

        return new JsonResponse($this->doResponse->doResponse($userData));
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): JsonResponse
    {
        $bearerCookie = Cookie::create('BEARER')
            ->withValue('')
            ->withExpires(new \DateTime('-1 day'))
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSameSite('None')
            ->withSecure(true);

        $refreshTokenCookie = Cookie::create('REFRESH_TOKEN')
            ->withValue('')
            ->withExpires(new \DateTime('-1 day'))
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSameSite('None')
            ->withSecure(true);

        $response = new JsonResponse('Logout success');

        $response->headers->setCookie($bearerCookie);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }

    #[Route('/api/change-password', name: 'change_password')]
    public function changePassword(UserPasswordHasherInterface $passwordHasher,
                                   ActionLoggerService $actionLoggerService): JsonResponse
    {
        $data = $this->request->getCurrentRequest()->toArray();

        if(!isset($data['new_password']) && !isset($data['old_password'])) {
            return new JsonResponse($this->doResponse->doErrorJsonResponse('Old password and new password are required'), 400);
        }

        $currentUser = $this->getUser();

        if(!$currentUser) {
            return $this->doResponse->doErrorJsonResponse('User not found', 400);
        }

        if(!$passwordHasher->isPasswordValid($currentUser, $data['old_password'])) {
            return $this->doResponse->doErrorJsonResponse('Old password is incorrect', 400);
        }

        $newPassword = $data['new_password'];

        if(strlen($newPassword) < 8) {
            return $this->doResponse->doErrorJsonResponse('New password must be at least 8 characters long', 400);
        }

        $currentUser->setPassword($passwordHasher->hashPassword($currentUser, $newPassword));

        $this->doctrine->persist($currentUser);
        $this->doctrine->flush();

        $actionLoggerService->logAction('Change password', $currentUser);

        return new JsonResponse('Change password success');
    }
}

