<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\PasswordResetService;
use PHPUnit\Framework\TestCase;

final class PasswordResetServiceTest extends TestCase
{
    public function testCreateTokenSetsHashAndExpiryAndValidates(): void
    {
        $service = new PasswordResetService();
        $user = new User();

        $token = $service->createToken($user, 60);

        self::assertNotSame('', $token);
        self::assertNotNull($user->getPasswordResetTokenHash());
        self::assertNotNull($user->getPasswordResetTokenExpiresAt());

        self::assertTrue($service->isTokenValid($user, $token));
        self::assertFalse($service->isTokenValid($user, $token.'x'));
        self::assertFalse($service->isTokenValid($user, ''));
    }

    public function testExpiredTokenIsInvalid(): void
    {
        $service = new PasswordResetService();
        $user = new User();

        $token = $service->createToken($user, 1);
        $user->setPasswordResetTokenExpiresAt((new \DateTimeImmutable())->modify('-1 second'));

        self::assertFalse($service->isTokenValid($user, $token));
    }
}


