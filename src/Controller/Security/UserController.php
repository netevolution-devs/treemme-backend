<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\UserService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


    #[Route('/backoffice/user', name: 'add_user', methods: ['POST'])]
    public function addUser(
        UserPasswordHasherInterface $passwordHasher,
        CreateMethodsByInput $createMethodsByInput,
        ValidatorInterface $validator,
        ValidatorOutputFormatter $validatorOutputFormatter,
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

        $user->setEmail($email)
            ->setPassword($hashedPassword)
            ->setRoles($roles);

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

        return new JsonResponse($this->doResponse->doResponse(['id' => $user->getId()]));
    }

    #[Route('/whoami', name: 'whoami', methods: ['GET'])]
    public function whoami(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        return new JsonResponse($this->doResponse->doResponse($this->groupSerializer->serializeGroup($user,'detail')));
    }
}