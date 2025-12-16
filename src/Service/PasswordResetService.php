<?php

namespace App\Service;

use App\Entity\User;

final class PasswordResetService
{
    public const DEFAULT_TTL_SECONDS = 86400; // 24h

    /**
     * Generates a one-time token and stores only its hash on the user.
     *
     * Returns the raw token (must be sent to the user).
     */
    public function createToken(User $user, int $ttlSeconds = self::DEFAULT_TTL_SECONDS): string
    {
        $token = $this->generateToken();

        $user->setPasswordResetTokenHash($this->hashToken($token));
        $user->setPasswordResetTokenExpiresAt((new \DateTimeImmutable())->modify(sprintf('+%d seconds', $ttlSeconds)));

        return $token;
    }

    public function isTokenValid(User $user, string $token): bool
    {
        $token = trim($token);
        if ('' === $token) {
            return false;
        }

        $hash = $user->getPasswordResetTokenHash();
        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if (null === $hash || null === $expiresAt) {
            return false;
        }

        if ($expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        return hash_equals($hash, $this->hashToken($token));
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function generateToken(): string
    {
        $bytes = random_bytes(32);

        // base64url (no padding) for safe URLs
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}


