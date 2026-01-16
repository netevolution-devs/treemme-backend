<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Service\ActionLoggerService;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AuthController extends AbstractController
{
    private TotpService $totpService;
    private EntityManagerInterface $entityManager;
    private ActionLoggerService $actionLoggerService;
    private AuthenticationSuccessHandler $successHandler;

    public function __construct(
        TotpService $totpService,
        EntityManagerInterface $entityManager,
        ActionLoggerService $actionLoggerService,
        AuthenticationSuccessHandler $successHandler
    ) {
        $this->totpService = $totpService;
        $this->entityManager = $entityManager;
        $this->actionLoggerService = $actionLoggerService;
        $this->successHandler = $successHandler;
    }

    #[Route('/verify-totp', name: 'verify_totp', methods: ['POST'])]
    public function verifyTotp(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['totp_code'])) {
            return new JsonResponse(['error' => 'Dati TOTP mancanti'], 400);
        }

        $userCode = $data['user_code'];
        $totpCode = $data['totp_code'];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['user_code' => $userCode]);

        if (!$user) {
            return new JsonResponse(['error' => 'Utente non trovato'], 404);
        }

        if (!$user->isTotpEnabled()) {
            return new JsonResponse(['error' => 'TOTP non abilitato per questo utente'], 400);
        }

        if (!$this->totpService->verifyCode($user, $totpCode)) {
            $this->actionLoggerService->logAction('totp verification failed', [
                'user_code' => $userCode,
                'reason' => 'Invalid TOTP code',
            ]);
            return new JsonResponse(['error' => 'Codice TOTP non valido'], 401);
        }

        $this->actionLoggerService->logAction('totp verification success', [
            'user_code' => $userCode,
        ]);
        $firstAccess = false;
        if($user->getLastAccess() === false){
            $firstAccess = true;
        }

        $user->setLastAccess(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        return $this->successHandler->onAuthenticationSuccess($request, $token, $firstAccess);
    }
}
