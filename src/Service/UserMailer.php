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
    private ?string $lastFailureReason = null;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $from,
        #[Autowire('%env(MAILER_DSN)%')]
        private readonly string $mailerDsn,
        #[Autowire('%env(APP_URL)%')]
        private readonly string $appUrl,
    ) {}

    public function getLastFailureReason(): ?string
    {
        return $this->lastFailureReason;
    }

    /**
     * Sends a simple welcome email (e.g. after signup).
     *
     * Returns false if sending fails (or no recipient email).
     */
    public function sendWelcome(User $user): bool
    {
        $this->lastFailureReason = null;

        $to = trim((string) $user->getEmail());
        if ($to === '') {
            $this->lastFailureReason = 'Recipient email-ը դատարկ է։';
            return false;
        }

        if ($this->isMailerDisabled()) {
            $this->lastFailureReason = 'Mailer disabled է (MAILER_DSN-ը դատարկ է կամ null://)։';
            $this->logMailerDisabled('welcome', $to);
            return false;
        }

        $loginUrl = $this->absoluteUrl($this->router->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_PATH));
        $logoUrl = $this->getLogoUrl();

        try {
            $email = (new Email())
                ->from($this->from)
                ->to($to)
                ->subject('Բարի գալուստ AnGo')
                ->text($this->buildWelcomeText($user, $loginUrl))
                ->html($this->buildWelcomeHtml($user, $loginUrl, $logoUrl));

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $prefix = $e instanceof TransportExceptionInterface ? 'Transport error: ' : 'Mailer error: ';
            $this->lastFailureReason = $prefix.$this->sanitizeForUi($e->getMessage());
            $this->logSendFailure('welcome', $to, $e);
            return false;
        }

        return true;
    }

    /**
     * Sends a "set your password" email for admin-created accounts.
     */
    public function sendAccountSetup(User $user, string $token): bool
    {
        $this->lastFailureReason = null;

        $to = trim((string) $user->getEmail());
        if ($to === '') {
            $this->lastFailureReason = 'Recipient email-ը դատարկ է։';
            return false;
        }

        if ($this->isMailerDisabled()) {
            $this->lastFailureReason = 'Mailer disabled է (MAILER_DSN-ը դատարկ է կամ null://)։';
            $this->logMailerDisabled('account_setup', $to);
            return false;
        }

        $resetUrl = $this->absoluteUrl($this->router->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_PATH));
        $logoUrl = $this->getLogoUrl();

        try {
            $email = (new Email())
                ->from($this->from)
                ->to($to)
                ->subject('Սահմանեք ձեր գաղտնաբառը (AnGo)')
                ->text($this->buildAccountSetupText($user, $resetUrl))
                ->html($this->buildAccountSetupHtml($user, $resetUrl, $logoUrl))
            ;

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $prefix = $e instanceof TransportExceptionInterface ? 'Transport error: ' : 'Mailer error: ';
            $this->lastFailureReason = $prefix.$this->sanitizeForUi($e->getMessage());
            $this->logSendFailure('account_setup', $to, $e);
            return false;
        }

        return true;
    }

    /**
     * Sends a password reset email.
     */
    public function sendPasswordReset(User $user, string $token): bool
    {
        $this->lastFailureReason = null;

        $to = trim((string) $user->getEmail());
        if ($to === '') {
            $this->lastFailureReason = 'Recipient email-ը դատարկ է։';
            return false;
        }

        if ($this->isMailerDisabled()) {
            $this->lastFailureReason = 'Mailer disabled է (MAILER_DSN-ը դատարկ է կամ null://)։';
            $this->logMailerDisabled('password_reset', $to);
            return false;
        }

        $resetUrl = $this->absoluteUrl($this->router->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_PATH));
        $logoUrl = $this->getLogoUrl();

        try {
            $email = (new Email())
                ->from($this->from)
                ->to($to)
                ->subject('Գաղտնաբառի վերականգնում (AnGo)')
                ->text($this->buildPasswordResetText($user, $resetUrl))
                ->html($this->buildPasswordResetHtml($user, $resetUrl, $logoUrl))
            ;

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $prefix = $e instanceof TransportExceptionInterface ? 'Transport error: ' : 'Mailer error: ';
            $this->lastFailureReason = $prefix.$this->sanitizeForUi($e->getMessage());
            $this->logSendFailure('password_reset', $to, $e);
            return false;
        }

        return true;
    }

    private function isMailerDisabled(): bool
    {
        $dsn = strtolower(trim($this->mailerDsn));
        if ($dsn === '') {
            return true;
        }

        // Symfony's recommended "disable mailer" DSN.
        if (str_starts_with($dsn, 'null://')) {
            return true;
        }

        return false;
    }

    private function logMailerDisabled(string $type, string $to): void
    {
        // Use error_log so it shows in container logs (Railway) without requiring extra bundles.
        error_log(sprintf('[UserMailer] Mailer disabled (MAILER_DSN="%s"). Skipping "%s" email to "%s".', $this->sanitizeDsnForLogs($this->mailerDsn), $type, $to));
    }

    private function logSendFailure(string $type, string $to, \Throwable $e): void
    {
        error_log(sprintf('[UserMailer] Failed sending "%s" email to "%s": %s', $type, $to, $this->sanitizeForUi($e->getMessage())));
    }

    private function sanitizeDsnForLogs(string $dsn): string
    {
        $dsn = trim($dsn);
        if ($dsn === '') {
            return '';
        }

        // Mask credentials/keys in case someone logs full DSN.
        // Examples:
        // - smtp://user:pass@host:587 -> smtp://***@host:587
        // - sendgrid+api://KEY@default -> sendgrid+api://***@default
        return preg_replace('~://([^@/]+)@~', '://***@', $dsn) ?? '***';
    }

    private function sanitizeForUi(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '';
        }

        // Mask any embedded DSN/API keys in exception messages.
        return preg_replace('~://([^@\s/]+)@~', '://***@', $message) ?? '***';
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

    private function buildWelcomeHtml(User $user, string $loginUrl, ?string $logoSrc): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return $this->buildButtonEmailHtml(
            title: 'Բարի գալուստ AnGo',
            name: $name,
            bodyText: "Ձեր հաշիվը ստեղծվել է AnGo համակարգում։\n\nՄուտք գործելու համար սեղմեք կոճակը՝",
            buttonText: 'Մուտք գործել',
            buttonUrl: $loginUrl,
            noteText: 'Եթե դուք չեք ստեղծել այս հաշիվը, դիմեք ադմինին։',
            logoSrc: $logoSrc,
        );
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

    private function buildAccountSetupHtml(User $user, string $resetUrl, ?string $logoSrc): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return $this->buildButtonEmailHtml(
            title: 'Սահմանեք ձեր գաղտնաբառը',
            name: $name,
            bodyText: "Ձեր հաշիվը ստեղծվել է AnGo համակարգում։\n\nԳաղտնաբառ սահմանելու համար սեղմեք կոճակը՝",
            buttonText: 'Սահմանել գաղտնաբառը',
            buttonUrl: $resetUrl,
            noteText: 'Այս հղումը ժամանակավոր է (24 ժամ)։',
            logoSrc: $logoSrc,
        );
    }

    private function buildPasswordResetHtml(User $user, string $resetUrl, ?string $logoSrc): string
    {
        $name = trim((string) $user->getFirstName());
        if ($name === '') {
            $name = 'ընկեր';
        }

        return $this->buildButtonEmailHtml(
            title: 'Գաղտնաբառի վերականգնում',
            name: $name,
            bodyText: "Դուք խնդրել եք վերականգնել AnGo հաշվի գաղտնաբառը։\n\nՆոր գաղտնաբառ սահմանելու համար սեղմեք կոճակը՝",
            buttonText: 'Սահմանել նոր գաղտնաբառը',
            buttonUrl: $resetUrl,
            noteText: 'Եթե դուք չեք կատարել այս հարցումը, պարզապես անտեսեք այս նամակը։',
            logoSrc: $logoSrc,
        );
    }

    private function buildButtonEmailHtml(
        string $title,
        string $name,
        string $bodyText,
        string $buttonText,
        string $buttonUrl,
        string $noteText,
        ?string $logoSrc = null,
    ): string {
        $esc = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $titleEsc = $esc($title);
        $nameEsc = $esc($name);
        $bodyHtml = nl2br($esc($bodyText));
        $buttonTextEsc = $esc($buttonText);
        $urlEsc = $esc($buttonUrl);
        $noteHtml = nl2br($esc($noteText));

        $logoHtml = '';
        if (null !== $logoSrc && '' !== trim($logoSrc)) {
            $logoSrcEsc = $esc($logoSrc);
            $logoHtml = <<<HTML
      <div style="margin:0 0 14px 0;">
        <img src="{$logoSrcEsc}" alt="AnGo" style="height:44px;width:auto;display:block;">
      </div>
HTML;
        }

        return <<<HTML
<!doctype html>
<html lang="hy">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$titleEsc} | AnGo</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Inter,'Noto Sans Armenian',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#111827;color:#ffffff;padding:18px 22px;border-radius:16px 16px 0 0;">
      <div style="font-size:18px;font-weight:800;letter-spacing:0.2px;">{$titleEsc}</div>
    </div>
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 16px 16px;padding:22px;">
{$logoHtml}
      <p style="margin:0 0 12px 0;font-size:16px;line-height:1.6;color:#111827;">Բարև {$nameEsc}։</p>
      <p style="margin:0 0 18px 0;font-size:14px;line-height:1.7;color:#374151;">{$bodyHtml}</p>

      <div style="margin:18px 0 16px 0;">
        <a href="{$urlEsc}" style="display:inline-block;background:#db2777;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:800;">
          {$buttonTextEsc}
        </a>
      </div>

      <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">Եթե կոճակը չի աշխատում, բացեք այս հղումը՝</p>
      <p style="margin:6px 0 0 0;font-size:12px;line-height:1.6;color:#2563eb;word-break:break-all;">
        <a href="{$urlEsc}" style="color:#2563eb;text-decoration:underline;">{$urlEsc}</a>
      </p>

      <p style="margin:16px 0 0 0;font-size:12px;line-height:1.6;color:#6b7280;">{$noteHtml}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;">
      <div style="font-size:12px;color:#9ca3af;">AnGo</div>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function absoluteUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '' || !str_starts_with($path, '/')) {
            return $path;
        }

        $base = $this->getBaseUrl();
        if ($base === '') {
            return $path;
        }

        return $base.$path;
    }

    private function getBaseUrl(): string
    {
        $base = trim($this->appUrl);
        if ($base !== '') {
            return rtrim($base, '/');
        }

        $home = $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return rtrim($home, '/');
    }

    private function getLogoUrl(): ?string
    {
        $base = $this->getBaseUrl();
        if ($base === '') {
            return null;
        }

        return $base.'/uploads/photos/ango-logo.png';
    }
}


