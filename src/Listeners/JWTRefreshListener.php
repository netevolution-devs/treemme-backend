<?php

namespace App\Listeners;

use Symfony\Component\HttpFoundation\Response;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Cookie;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


class JWTRefreshListener
{
    private JWTTokenManagerInterface $tokenManager;
    private UserService $userService;


    public function __construct(JWTTokenManagerInterface $tokenManager, UserService $userService)
    {
        $this->tokenManager = $tokenManager;
        $this->userService = $userService;

    }


    /**
     * @param JWTExpiredEvent $event
     */
    public function onJWTExpired(JWTExpiredEvent $event)
    {
        $request = $event->getRequest();
        $refreshToken = $request->cookies->get('REFRESH_TOKEN');
        $user = $this->getUserFromRefreshToken($refreshToken);

        $newToken = $this->refreshToken($user);

        $newResponse = new Response();
        $newResponse->headers->setCookie(
            new Cookie(
                'BEARER',
                $newToken,
                (new \DateTime())->add(new \DateInterval('PT86400S')),
                '/',
                null,
                true,
                true,
                true,
                'None'
            )
        );

        $event->setResponse($newResponse);
    }


    private function refreshToken($user)
    {
        $newToken = $this->tokenManager->create($user);

        return $newToken;
    }

    private function getUserFromRefreshToken($refreshToken)
    {
        $user = $this->userService->getUserByRefreshToken($refreshToken);

        return $user;
    }
}

