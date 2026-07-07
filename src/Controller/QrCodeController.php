<?php

namespace App\Controller;

use App\Service\QrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'qr_code')]
final class QrCodeController extends AbstractController
{
    private $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    #[Route('/qr-code', name: 'generate_qr_code', methods: ['GET'])]
    public function generateQrCode(Request $request): Response
    {
        $url = $request->query->get('url');
        if (!$url) {
            return new JsonResponse(['error' => 'URL parameter is missing'], 400);
        }

        $qrCodeDataUri = $this->qrCodeService->generateQrCode($url);

        if ($request->headers->get('Accept') === 'application/json') {
            return new JsonResponse(['qrCode' => $qrCodeDataUri]);
        }

        $data = explode(',', $qrCodeDataUri);
        $content = base64_decode($data[1]);

        return new Response($content, 200, [
            'Content-Type' => 'image/png',
        ]);
    }
}
