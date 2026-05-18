<?php

namespace App\Listeners;

use App\Service\ActionLoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ActionLoggerListener implements EventSubscriberInterface
{
    private ActionLoggerService $actionLoggerService;

    public function __construct(ActionLoggerService $actionLoggerService)
    {
        $this->actionLoggerService = $actionLoggerService;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Logghiamo solo se la richiesta ha avuto successo (2xx) e se è un metodo di modifica
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $method = $request->getMethod();
            $path = $request->getPathInfo();

            if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                
                $action = $request->attributes->get('_route') ?? 'unknown_action';

                if (str_contains($action, 'login') || str_contains($action, 'verify_totp')) {
                    return;
                }
                
                // Proviamo a recuperare l'ID del record dai parametri della route
                $recordId = $request->attributes->get('id');
                
                // Se non c'è 'id', proviamo con altri nomi comuni
                if (!$recordId) {
                    $recordId = $request->attributes->get('user_code') ?: $request->attributes->get('code');
                }

                // Se l'ID è una stringa che rappresenta un numero, lo convertiamo
                if (is_numeric($recordId)) {
                    $recordId = (int) $recordId;
                } elseif (is_string($recordId)) {
                    $recordId = null; 
                }

                $this->actionLoggerService->logAction($action, [], false, null, is_int($recordId) ? $recordId : null);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }
}
