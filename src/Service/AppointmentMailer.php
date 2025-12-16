<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AppointmentMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $from,
    ) {}

    /**
     * Sends a "booking created" email to the client.
     *
     * Returns false if sending fails (or no recipient email).
     */
    public function sendBookingCreated(Appointment $appointment): bool
    {
        $to = trim((string) $appointment->getClientEmail());
        if ($to === '') {
            return false;
        }

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject(sprintf('Ամրագրումը ստացվել է (ID #%d)', (int) $appointment->getId()))
            ->text($this->buildBookingCreatedText($appointment));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * Sends a status change email to the client.
     *
     * Returns false if sending fails (or no recipient email).
     */
    public function sendStatusChanged(Appointment $appointment, string $oldStatus): bool
    {
        $to = trim((string) $appointment->getClientEmail());
        if ($to === '') {
            return false;
        }

        $newStatus = (string) $appointment->getStatus();

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject(sprintf(
                'Ամրագրումը թարմացվել է՝ %s (ID #%d)',
                $this->statusLabel($newStatus),
                (int) $appointment->getId()
            ))
            ->text($this->buildStatusChangedText($appointment, $oldStatus, $newStatus));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    private function buildBookingCreatedText(Appointment $appointment): string
    {
        $name = (string) $appointment->getClientName();
        $artist = (string) ($appointment->getArtist()?->getUser() ?? '');
        $service = (string) ($appointment->getService()?->getName() ?? '');

        $start = $appointment->getStartDatetime();
        $startText = $start ? $start->format('Y-m-d H:i') : '-';

        $duration = $appointment->getDurationHuman();
        $status = $this->statusLabel((string) $appointment->getStatus());

        return trim(<<<TEXT
Բարև {$name}։

Ձեր ամրագրումը ստացվել է և գտնվում է «{$status}» կարգավիճակում։

Ծառայություն: {$service}
Վարպետ: {$artist}
Սկիզբ: {$startText}
Տևողություն: {$duration}

Շնորհակալություն,
AnGo
TEXT);
    }

    private function buildStatusChangedText(Appointment $appointment, string $oldStatus, string $newStatus): string
    {
        $name = (string) $appointment->getClientName();
        $artist = (string) ($appointment->getArtist()?->getUser() ?? '');
        $service = (string) ($appointment->getService()?->getName() ?? '');

        $start = $appointment->getStartDatetime();
        $startText = $start ? $start->format('Y-m-d H:i') : '-';

        $duration = $appointment->getDurationHuman();

        $oldLabel = $this->statusLabel($oldStatus);
        $newLabel = $this->statusLabel($newStatus);

        return trim(<<<TEXT
Բարև {$name}։

Ձեր ամրագրման կարգավիճակը թարմացվել է՝ «{$oldLabel}» → «{$newLabel}»։

Ծառայություն: {$service}
Վարպետ: {$artist}
Սկիզբ: {$startText}
Տևողություն: {$duration}

Շնորհակալություն,
AnGo
TEXT);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Appointment::STATUS_PENDING => 'Սպասման մեջ',
            Appointment::STATUS_CONFIRMED => 'Հաստատված',
            Appointment::STATUS_COMPLETED => 'Ավարտված',
            Appointment::STATUS_CANCELED => 'Չեղարկված',
            default => $status,
        };
    }
}


