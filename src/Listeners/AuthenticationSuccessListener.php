<?php
// src/Listeners/AuthenticationSuccessListener.php
namespace App\Listeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;


class AuthenticationSuccessListener
{
    private $secure = false;

    public function __construct()
    {

    }

    /**
     * @throws \Exception
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event)
    {
        $response = $event->getResponse();
        $data = $event->getData();

        $token = $data['token'];
        unset($data['token']);
        $event->setData($data);

        // todo set the interval time using token_ttl value in lexik_jwt_authentication.yaml
        // https://github.com/konshensx16/symfony-todo-backend

        $response->headers->setCookie(
            new Cookie(
                'BEARER',
                $token,
                (new \DateTime())->add(new \DateInterval('PT86400S')),
                '/',
                null,
                true,
                true,
                true,
                'None'
            )
        );
    }

}
