<?php

namespace App\Listeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshedTokenListener implements EventSubscriberInterface
{

    private $cookieSecure = false;

    public function __construct()
    {

    }

    public function setRefreshToken(AuthenticationSuccessEvent $event)
    {
        $refreshToken = $event->getData()['refresh_token'];
        $response = $event->getResponse();

        // todo set the interval time using token_ttl value in gesdinet_jwt_refresh_token.yaml
        // https://github.com/konshensx16/symfony-todo-backend

        if ($refreshToken) {
            $response->headers->setCookie(new Cookie('REFRESH_TOKEN',
                $refreshToken,
                (new \DateTime())->add(new \DateInterval('PT86400S')),
                '/',
                null,
                true,
                true,
                true,
                'None'
            ));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => [
                ['setRefreshToken']
            ]
        ];
    }
}
