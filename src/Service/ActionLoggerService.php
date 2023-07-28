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
     * @return void
     *
     */
    public function logAction(string $action, object|array $data): void
    {
        $scope = str_contains($this->requestStack->getCurrentRequest()->getPathInfo(), 'api') ? 'api' : 'to_be_defined';
        $extra = [
            'ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
            'content' => $this->requestStack->getCurrentRequest()->getContent(),
            'headers' => $this->requestStack->getCurrentRequest()->headers->all(),
            'method' => $this->requestStack->getCurrentRequest()->getMethod(),
            'locale' => $this->requestStack->getCurrentRequest()->getLocale()
        ];

        $log = new ActionsLogs();
        $log->setScope($scope)
            ->setAction($action)
            ->setData((array)$data)
            ->setExtra($extra);

        $user = $this->userService->getCurrentUser();
        if($user){
            $log->setUser($user->getId());
        }

        $em = $this->em->getManager('logger');
        $em->persist($log);
        $em->flush();
    }
}