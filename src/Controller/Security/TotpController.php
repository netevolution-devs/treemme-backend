<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/totp')]
class TotpController extends AbstractController
{
    private TotpService $totpService;
    private EntityManagerInterface $doctrine;

    public function __construct(TotpService $totpService, EntityManagerInterface $doctrine)
    {
        $this->totpService = $totpService;
        $this->doctrine = $doctrine;
    }

    /**
     * Genera un nuovo secret TOTP e restituisce i dati per il QR code
     */
    #[Route('/setup/{user_code}', name: 'totp_setup', methods: ['POST'])]
    public function setupTotp(string $user_code): JsonResponse
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['user_code' => $user_code]);

        if ($user->isTotpEnabled()) {
            return new JsonResponse([
                'error' => 'TOTP è già abilitato per questo utente'
            ], 400);
        }

        if(!$user->getTotpSecret()){
            $secret = $this->totpService->generateSecret($user);
        }

        $qrData = $this->totpService->getQrCodeData($user);

        return new JsonResponse([
            'message' => 'Secret TOTP generato. Scansiona il QR code con la tua app di autenticazione.',
            'qr_data' => $qrData,
            'backup_codes' => $this->totpService->generateBackupCodes($user)
        ]);
    }

    /**
     * Verifica il codice TOTP e abilita l'autenticazione a due fattori
     */
    #[Route('/verify/{user_code}', name: 'totp_verify', methods: ['POST'])]
    public function verifyAndEnable(Request $request, string $user_code): JsonResponse
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['user_code' => $user_code]);

        $data = json_decode($request->getContent(), true);

        if (!isset($data['code'])) {
            return new JsonResponse(['error' => 'Codice TOTP richiesto'], 400);
        }

        $code = $data['code'];

        if ($this->totpService->enableTotp($user, $code)) {
            return new JsonResponse([
                'message' => 'TOTP abilitato con successo!',
                'enabled' => true
            ]);
        }

        return new JsonResponse([
            'error' => 'Codice TOTP non valido'
        ], 400);
    }

    /**
     * Disabilita TOTP (richiede password o codice TOTP per sicurezza)
     */
    #[Route('/disable/{user_code}', name: 'totp_disable', methods: ['POST'])]
    public function disableTotp(Request $request, string $user_code): JsonResponse
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['user_code' => $user_code]);

        if (!$user->isTotpEnabled()) {
            return new JsonResponse(['error' => 'TOTP non è abilitato'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['totp_code'])) {
            return new JsonResponse(['error' => 'Codice TOTP richiesto per disabilitare'], 400);
        }

        if ($this->totpService->verifyCode($user, $data['totp_code'])) {
            $this->totpService->disableTotp($user);

            return new JsonResponse([
                'message' => 'TOTP disabilitato con successo',
                'enabled' => false
            ]);
        }

        return new JsonResponse(['error' => 'Codice TOTP non valido'], 400);
    }

    /**
     * Restituisce lo stato TOTP dell'utente
     */
    #[Route('/status/{user_code}', name: 'totp_status', methods: ['GET'])]
    public function getTotpStatus(string $user_code): JsonResponse
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['user_code' => $user_code]);

        return new JsonResponse([
            'enabled' => $user->isTotpEnabled(),
            'enabled_at' => $user->getTotpEnabledAt()?->format('c')
        ]);
    }
}
