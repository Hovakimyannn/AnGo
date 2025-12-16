<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class UserMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $from,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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

        $logoCid = $this->embedLogo($email);
        if (null !== $logoCid) {
            $email->html($this->buildWelcomeHtml($user, $loginUrl, $logoCid));
        }

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
            ->text($this->buildAccountSetupText($user, $resetUrl))
        ;

        $logoCid = $this->embedLogo($email);
        $email->html($this->buildAccountSetupHtml($user, $resetUrl, $logoCid));

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
            ->text($this->buildPasswordResetText($user, $resetUrl))
        ;

        $logoCid = $this->embedLogo($email);
        $email->html($this->buildPasswordResetHtml($user, $resetUrl, $logoCid));

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

    private function buildWelcomeHtml(User $user, string $loginUrl, string $logoSrc): string
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

    private function embedLogo(Email $email): ?string
    {
        try {
            $candidates = [
                $this->projectDir.'/public/uploads/photos/ango-logo.png',
            ];

            $path = null;
            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $path = $candidate;
                    break;
                }
            }
            if (null === $path) {
                return null;
            }

            $part = DataPart::fromPath($path, basename($path))->asInline();
            $part->setContentId('ango-logo@ango');
            $email->addPart($part);

            return 'cid:'.$part->getContentId();
        } catch (\Throwable) {
            return null;
        }
    }
}


