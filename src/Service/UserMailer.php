<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class UserMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $from,
    ) {}

    /**
     * Sends a simple welcome email (e.g. after signup).
     *
     * Returns false if sending fails (or no recipient email).
     */
    public function sendWelcome(User $user): bool
    {
        $to = trim((string) $user->getEmail());
        if ($to === '') {
            return false;
        }

        $loginUrl = $this->router->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject('Բարի գալուստ AnGo')
            ->text($this->buildWelcomeText($user, $loginUrl));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * Sends a "set your password" email for admin-created accounts.
     */
    public function sendAccountSetup(User $user, string $token): bool
    {
        $to = trim((string) $user->getEmail());
        if ($to === '') {
            return false;
        }

        $resetUrl = $this->router->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject('Սահմանեք ձեր գաղտնաբառը (AnGo)')
            ->text($this->buildAccountSetupText($user, $resetUrl));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * Sends a password reset email.
     */
    public function sendPasswordReset(User $user, string $token): bool
    {
        $to = trim((string) $user->getEmail());
        if ($to === '') {
            return false;
        }

        $resetUrl = $this->router->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject('Գաղտնաբառի վերականգնում (AnGo)')
            ->text($this->buildPasswordResetText($user, $resetUrl));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    private function buildWelcomeText(User $user, string $loginUrl): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return trim(<<<TEXT
Բարև {$name}։

Ձեր հաշիվը ստեղծվել է AnGo համակարգում։

Մուտք գործելու համար բացեք՝ {$loginUrl}

Շնորհակալություն,
AnGo
TEXT);
    }

    private function buildAccountSetupText(User $user, string $resetUrl): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return trim(<<<TEXT
Բարև {$name}։

Ձեր հաշիվը ստեղծվել է AnGo համակարգում։

Գաղտնաբառ սահմանելու համար բացեք՝ {$resetUrl}

Այս հղումը ժամանակավոր է (24 ժամ)։

Շնորհակալություն,
AnGo
TEXT);
    }

    private function buildPasswordResetText(User $user, string $resetUrl): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return trim(<<<TEXT
Բարև {$name}։

Դուք խնդրել եք վերականգնել AnGo հաշվի գաղտնաբառը։

Նոր գաղտնաբառ սահմանելու համար բացեք՝ {$resetUrl}

Եթե դուք չեք կատարել այս հարցումը, պարզապես անտեսեք այս նամակը։

Շնորհակալություն,
AnGo
TEXT);
    }
}


