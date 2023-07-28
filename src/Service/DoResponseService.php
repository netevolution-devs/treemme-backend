<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class DoResponseService
{
    private string $environment;
    private RequestStack $requestStack;
    private TokenService $tokenService;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(
        KernelInterface $kernel,
        RequestStack $requestStack,
        TokenService $tokenService
    )
    {
        $this->environment = $kernel->getEnvironment();
        $this->requestStack = $requestStack;
        $this->tokenService = $tokenService;
    }

    /**
     * Compose complete json response of valid data
     *
     * @param $data
     * @param array $pagination Optional
     * @param string $status Optional
     * @param int $status_code Optional - default 200
     * @param string $error Optional
     * @return array
     */

    public function doResponse( $data, array $pagination = [], string $status = 'ok', int $status_code = 200, string $error = ''): array

    {

        return array(
            'status' => $status,
            'status_code' => $status_code,
            'error' => $error,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
            'pagination' => $pagination,
            'jwt' => [
                'created_at' => $this->tokenService->getTokenTimpestamp('iat'),
                'deadline' => $this->tokenService->getTokenTimpestamp('exp')
            ],
            'data' => $data,
        );
    }

    /**
     * Compose complete json response of an error
     *
     * @param string $error_message
     * @param string $error_file Optional
     * @param string $status Optional - default 400
     * @param int $status_code Optional
     * @return array
     */
    public function doErrorResponse(
        string|array $error_message, string $error_file = "", string $status = 'ko', int $status_code = 400, ): array
    {
        $result = array(
            'status' => $status,
            'status_code' => $status_code,
            'error' => [
                'message' => '',
                'file' => '',
                'validation' => []
            ],
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
            'pagination' => [],
            'jwt' => [
                'created_at' => $this->tokenService->getTokenTimpestamp('iat'),
                'deadline' => $this->tokenService->getTokenTimpestamp('exp')
            ],
            'data' => [],
        );

        if(is_array($error_message)){
            $result['error'] = [
                'message' => '',
                'file' => '',
                'validation' => $error_message
            ];
        }
        else{
            $result['error'] = [
                'message' => $error_message,
                'file' => $error_file,
                'validation' => []
            ];
        }

        // hide errors in production env
        if ($this->environment === 'prod') {
            $result['error'] = [
                'message' => '',
                'file' => '',
                'validation' => []
            ];
        }

        return $result;
    }

}