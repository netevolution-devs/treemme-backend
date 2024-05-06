<?php

namespace App\Listeners;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class JsonExceptionListener
{
    private string $environment;
    private RequestStack $request;

    public function __construct(KernelInterface $kernel, RequestStack $requestStack)
    {
        $this->environment = $kernel->getEnvironment();
        $this->request = $requestStack;
    }

    private function generateErrorMessage(\Throwable $exception, $code): array
    {
        $message = [
            'status_code' => $code,
            'error' => [
                'message' => $exception->getMessage(),
            ],
        ];

        if ($this->environment === 'dev') {
            // Add debug information if in 'dev' environment
            $message['error'] += $this->addDebugInfoToMessage($exception);
        }

        return $message;
    }

    private function addDebugInfoToMessage(\Throwable $exception): array
    {
        $currentRequest = $this->request->getCurrentRequest();
        $rawContent = null;

        if(!$currentRequest) {
            return [
                'file' => $exception->getFile() . " - line " . $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $content = $currentRequest->getContent();
        $decodedContent = json_decode($content, true);
        if(json_last_error() !== JSON_ERROR_NONE){
            $rawContent = ['raw' => $content];
        } else {
            $rawContent = $decodedContent;
        }

        return [
            'file' => $exception->getFile() . " - line " . $exception->getLine(),
            'message' => $exception->getMessage(),
            'http_method' => $currentRequest->getMethod(),
            'uri' => $currentRequest->getUri(),
            'request' => $currentRequest->request->all(),
            'raw_content' => $rawContent,
            'headers' => $currentRequest->headers->all(),
            'cookies' => $currentRequest->cookies->all(),
            'trace' => $exception->getTraceAsString(),
            'server_params' => $currentRequest->server->all(),
        ];
    }

    private function getRequestData(Request $request): array
    {
        $parameters = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        return $parameters;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $code = $exception instanceof HttpExceptionInterface ?
            $exception->getStatusCode() :
            Response::HTTP_INTERNAL_SERVER_ERROR;

        $message = $this->generateErrorMessage($exception, $code);

        $response = new Response();
        if($exception instanceof HttpExceptionInterface) {
            $response->headers->replace($exception->getHeaders());
        }
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($message, JSON_THROW_ON_ERROR));

        $event->setResponse($response);
    }
}