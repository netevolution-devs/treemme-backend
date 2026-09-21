<?php

namespace App\EventSubscriber;

use App\Service\ActionLoggerService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActionLoggerService $actionLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->actionLogger->logAction(
            action: 'login',
            data: [
                'username' => $user->getUserIdentifier(),
            ],
            scope: 'security'
        );
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user) {
            $this->actionLogger->logAction(
                action: 'logout',
                data: [
                    'username' => $user->getUserIdentifier(),
                ],
                scope: 'security'
            );
        }
    }
}
