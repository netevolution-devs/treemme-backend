<?php
// src/EventListener/LoginSuccessListener.php
namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;


class LoginSuccessListener
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

        $response->headers->setCookie(
            new Cookie('BEARER', $token, (new \DateTime())->add(new \DateInterval('PT3600S')), '/', null, $this->secure)
        );

    }

}
