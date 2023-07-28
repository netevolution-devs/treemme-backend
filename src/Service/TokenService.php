<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Namshi\JOSE\JWS;

class TokenService
{
    private TokenStorageInterface $tokenStorage;
    private RequestStack $request;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->request = $requestStack;
    }

    public function getToken()
    {

        return $this->tokenStorage->getToken();
    }

    public function getTokenTimpestamp(string $timestamp) {

        $headers = $this->request->getCurrentRequest()->headers;
        $token = substr($headers->get('authorization'),7);

        if (empty($token)) {

            return null;
        }

        try {
            $jws = JWS::load($token);
        } catch (\InvalidArgumentException $e) {

            return null;
        }

        $event_time =$jws->getPayload()[$timestamp];

        return $this->epochToDatetime($event_time);
    }

    public function epochToDatetime(int $epoch) {

        $dt = new \DateTime("@$epoch");

        return $dt->format('Y-m-d H:i:s');
    }
}