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


    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns a new User",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"detail"}))
     *     )
     * )
     *
     * @OA\Tag(name="add_user")
     * @Security(name="Bearer")
     *
     */
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

        $email = $data['email'];
        $password = $data['password'];

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

            return new JsonResponse($this->doResponse->doErrorResponse('indirizzo email già esistente',$e->getFile()));
        }

        $actionLoggerService->logAction('add_new_user', $this->groupSerializer->serializeGroup($user, 'user_detail'));

        return new JsonResponse($this->doResponse->doResponse(['id' => $user->getId()]));
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
}