<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TotpService
{
    private EntityManagerInterface $entityManager;
    private string $appName;

    public function __construct(EntityManagerInterface $entityManager, string $appName)
    {
        $this->entityManager = $entityManager;
        $this->appName = $appName;
    }

    /**
     * Genera un nuovo secret TOTP per l'utente
     */
    public function generateSecret(User $user): string
    {
        // Genera un secret casuale di 32 caratteri (160 bit)
        $secret = trim(Base32::encodeUpper(random_bytes(20)), '=');

        $user->setTotpSecret($secret);
        $user->setTotpEnabled(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $secret;
    }

    /**
     * Genera l'URL del QR Code per Google/Microsoft Authenticator
     */
    public function getQrCodeUrl(User $user): string
    {
        if (!$user->getTotpSecret()) {
            throw new \InvalidArgumentException('L\'utente non ha un secret TOTP configurato');
        }

        $totp = TOTP::create($user->getTotpSecret());
        $totp->setLabel($user->getEmail());
        $totp->setIssuer($this->appName);

        return $totp->getProvisioningUri();
    }

    /**
     * Genera l'URL del QR Code con formato specifico per i parametri
     */
    public function getQrCodeData(User $user): array
    {
        $qrUrl = $this->getQrCodeUrl($user);

        return [
            'secret' => $user->getTotpSecret(),
            'qr_url' => $qrUrl,
            'manual_entry_key' => $user->getTotpSecret(),
            'issuer' => $this->appName,
            'account' => $user->getEmail()
        ];
    }

    /**
     * Verifica un codice TOTP
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->getTotpSecret()) {
            return false;
        }

        $totp = TOTP::create($user->getTotpSecret());

        return $totp->verify($code, null, 1);
    }

    /**
     * Abilita TOTP per l'utente dopo la verifica del primo codice
     */
    public function enableTotp(User $user, string $verificationCode): bool
    {
        if ($this->verifyCode($user, $verificationCode)) {
            $user->setTotpEnabled(true);
            $user->setTotpEnabledAt(new \DateTimeImmutable());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    /**
     * Disabilita TOTP per l'utente
     */
    public function disableTotp(User $user): void
    {
        $user->setTotpEnabled(false);
        $user->setTotpSecret(null);
        $user->setTotpEnabledAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Genera codici di backup (opzionale, per sicurezza aggiuntiva)
     */
    public function generateBackupCodes(User $user, int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }
}
