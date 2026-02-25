<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActionLoggerService;
use App\Service\PasswordService;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\Translation\TranslatorInterface;


class CustomAuthenticator extends AbstractAuthenticator
{
    private HttpUtils $httpUtils;
    private UserProviderInterface $userProvider;
    private AuthenticationSuccessHandler $successHandler;
    private UserPasswordHasherInterface $passwordHasher;
    private TranslatorInterface $translator;
//    private RateLimiterFactory $loginLimiter;
    private ActionLoggerService $actionLoggerService;
    private TotpService $totpService;
    private EntityManagerInterface $entityManager;


    public function __construct(
        HttpUtils                    $httpUtils,
        UserProviderInterface        $userProvider,
        AuthenticationSuccessHandler $successHandler,
        EntityManagerInterface       $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordService             $passwordService,
        TranslatorInterface         $translator,
//        RateLimiterFactory          $loginLimiter,
        ActionLoggerService         $actionLoggerService,
        TotpService                 $totpService,
    ) {
        $this->httpUtils = $httpUtils;
        $this->userProvider = $userProvider;
        $this->successHandler = $successHandler;
        $this->passwordHasher = $passwordHasher;
        $this->passwordService = $passwordService;
        $this->translator = $translator;
//        $this->loginLimiter = $loginLimiter;
        $this->actionLoggerService = $actionLoggerService;
        $this->totpService = $totpService;
        $this->entityManager = $entityManager;
    }

    public function supports(Request $request): ?bool
    {
        return ($this->httpUtils->checkRequestPath($request, '/login') &&
                $request->isMethod('POST'));
    }

    public function authenticate(Request $request): Passport
    {
        $currentPath = $request->getPathInfo();
        $isAppLogin = $currentPath === '/login';
        $isBackofficeLogin = $currentPath === '/backoffice/login';

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('auth.error.missing_credentials', [], 'messages'));
        }

        $email = $data['email'];
        $password = $data['password'];

//        $limiterKey = $request->getClientIp();
//        $limiter = $this->loginLimiter->create($limiterKey);
//        $limit = $limiter->consume();

        if ($email === '' || $password === '') {
            throw new AuthenticationException('Empty credentials');
        }

//        if (!$limit->isAccepted()) {
//            $waitSeconds = $limit->getRetryAfter()->getTimestamp() - time();
//            throw new CustomUserMessageAuthenticationException(
//                $this->translator->trans('auth.error.too_many_attempts',
//                    ['%seconds%' => $waitSeconds], 'messages')
//            );
//        }

//        $remainingAttempts = $limit->getRemainingTokens();

        try {
            $user = $this->userProvider->loadUserByIdentifier($email);
        } catch (\Exception $e) {
            $this->actionLoggerService->logAction('login failed', [
                'email' => $email,
                'reason' => 'Invalid credentials'
            ]);
            throw new CustomUserMessageAuthenticationException('Credenziali Errate ');
        }


        if (!$this->passwordHasher->isPasswordValid($user, $password)) {

            $this->actionLoggerService->logAction('login failed', [
                'email' => $email,
                'reason' => 'invalid password'
//                'attempt' => $remainingAttempts,
            ]);
            throw new CustomUserMessageAuthenticationException('Credenziali Errate');
//            throw new CustomUserMessageAuthenticationException($this->translator->trans('auth.error.invalid_credentials',
//                    ["%attempts_left%" => $remainingAttempts], 'messages')
//            );
        }

        if ($user instanceof User) {

            if ($user->isTotpEnabled()) {
                throw new CustomUserMessageAuthenticationException(
                    'totp_required',
                    ['user_code' => $user->getUserCode(), 'requires_totp' => true],
                    200
                );
            }
        }

        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $limiterKey = $request->getClientIp();
//        $limiter = $this->loginLimiter->create($limiterKey);

//        $limiter->reset();

        $user = $token->getUser();

        $firstAccess = false;

        if ($user instanceof User && $user->getLastAccess() === null) {
            $firstAccess = true;
        }


        if($user instanceof User) {
            $user->setLastAccess(new \DateTimeImmutable());
            $em = $this->entityManager;
            $em->persist($user);
            $em->flush();
        }

        $this->actionLoggerService->logAction('login success', []);
        return $this->successHandler->onAuthenticationSuccess($request, $token, $firstAccess);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof CustomUserMessageAuthenticationException &&
            $exception->getMessage() === 'totp_required') {

            $messageData = $exception->getMessageData();

            return new JsonResponse([
                'error' => 'totp_required',
                'user_code' => $messageData['user_code'] ?? null,
                'requires_totp' => $messageData['requires_totp'] ?? true
            ], 200);
        }

        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }


    //    public function start(Request $request, ?AuthenticationException $authException = null): Response
    //    {
    //        /*
    //         * If you would like this class to control what happens when an anonymous user accesses a
    //         * protected page (e.g. redirect to /login), uncomment this method and make this class
    //         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //         *
    //         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //         */
    //    }
}
