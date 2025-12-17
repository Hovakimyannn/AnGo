<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class UserMailerTest extends TestCase
{
    public function testSendWelcomeDoesNotSendWhenMailerDisabled(): void
    {
        $mailer = new RecordingMailer();
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $service = new UserMailer(
            mailer: $mailer,
            router: $router,
            from: 'AnGo <noreply@example.test>',
            mailerDsn: 'null://null',
            appUrl: 'https://example.test',
        );

        $user = new User();
        $user->setEmail('user@example.test');

        self::assertFalse($service->sendWelcome($user));
        self::assertCount(0, $mailer->messages);
        self::assertNotNull($service->getLastFailureReason());
        self::assertStringContainsString('Mailer disabled', $service->getLastFailureReason());
    }

    public function testSendWelcomeBuildsAbsoluteUrlsAndSends(): void
    {
        $mailer = new RecordingMailer();
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('app_login', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/login');

        $service = new UserMailer(
            mailer: $mailer,
            router: $router,
            from: 'AnGo <noreply@example.test>',
            mailerDsn: 'smtp://user:pass@example.test:587',
            appUrl: 'https://example.test',
        );

        $user = new User();
        $user->setEmail('user@example.test');
        $user->setFirstName('Ani');

        self::assertTrue($service->sendWelcome($user));
        self::assertCount(1, $mailer->messages);

        $message = $mailer->messages[0];
        self::assertInstanceOf(Email::class, $message);
        /** @var Email $email */
        $email = $message;
        self::assertSame('Բարի գալուստ AnGo', $email->getSubject());

        $html = (string) $email->getHtmlBody();
        self::assertStringContainsString('https://example.test/login', $html);
        self::assertStringContainsString('https://example.test/uploads/photos/ango-logo.png', $html);
    }
}

final class RecordingMailer implements MailerInterface
{
    /** @var array<int, RawMessage> */
    public array $messages = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->messages[] = $message;
    }
}


