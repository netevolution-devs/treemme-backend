<?php

namespace App\Service;

use App\Entity\Logger\ActionsLogs;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class ActionLoggerService
{
    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;
    private ManagerRegistry $em;
    private UserService $userService;

    public function __construct(ManagerRegistry $em, RequestStack $requestStack, UserService $userService)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->userService = $userService;
    }

    /**
     * Logs area or backoffice user action
     * auto retrieves scope and extra data
     *
     * @param string $action action name
     * @param object|array $data entity modified (object) or other data (array)
     * @param bool $anonymous whether to log the user or not
     * @return void
     *
     */
    public function logAction(string $action, object|array $data = [], bool $anonymous = false, ?string $scope = null, ?int $recordId = null): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $path = $request?->getPathInfo() ?? '';

        if ($scope === null) {
            $scope = str_contains($path, 'api') ? 'api' : (str_contains($path, 'backoffice') ? 'backoffice' : 'other');
        }

        $extra = [
            'ip' => $request?->getClientIp(),
            'headers' => $request?->headers->all() ?? [],
            'method' => $request?->getMethod(),
            'path' => $path,
            'locale' => $request?->getLocale(),
        ];

        if ($recordId !== null) {
            $extra['record_id'] = $recordId;
        } else {
            // Se recordId è null, proviamo a vedere se era una stringa (es. user_code) passata nel path
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $recordIdAttr = $request->attributes->get('id') ?: ($request->attributes->get('user_code') ?: $request->attributes->get('code'));
                if ($recordIdAttr) {
                    $extra['record_id_string'] = $recordIdAttr;
                }
            }
        }

        $log = new ActionsLogs();
        $log->setScope($scope)
            ->setAction($action)
            ->setData(is_array($data) ? $data : [])
            ->setExtra($extra);

        if (!$anonymous) {
            $user = $this->userService->getCurrentUser();
            if ($user) {
                $log->setUser($user->getId());
            }
        }

        $em = $this->em->getManager('logger');
        $em->persist($log);
        $em->flush();
    }
}