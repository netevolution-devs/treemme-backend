<?php

namespace App\Service;

use App\Entity\Logger\ActionsLogs;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
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
     * @param string|null $scope
     * @param int|null $recordId
     * @param Request|null $request
     * @return void
     *
     */
    public function logAction(
        string $action,
        object|array $data = [],
        bool $anonymous = false,
        ?string $scope = null,
        ?int $recordId = null,
        ?Request $request = null
    ): void {
        $request = $request ?? $this->requestStack->getCurrentRequest();

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

        if ($request) {
            $body = $this->extractRequestBody($request);
            if (!empty($body)) {
                $extra['body'] = $body;
            }
        }

        if ($recordId !== null) {
            $extra['record_id'] = $recordId;
        } else {
            // Se recordId è null, proviamo a vedere se era una stringa (es. user_code) passata nel path
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

    private function extractRequestBody(Request $request): ?array
    {
        $contentType = $request->headers->get('CONTENT_TYPE', '');

        $data = null;
        if (str_contains($contentType, 'application/json')) {
            $content = $request->getContent();
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        if ($data === null && ($request->request->count() > 0)) {
            $data = $request->request->all();
        }

        if (is_array($data)) {
            return $this->maskSensitiveData($data);
        }

        return null;
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'plainpassword',
            'current_password',
            'new_password',
            'old_password',
            'token',
            'refreshtoken',
            'refresh_token',
            'totp',
            'secret',
            'authorization',
            'api_key',
            'apikey',
        ];

        $masked = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $masked[$key] = '******';
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}