<?php

namespace App\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Sends a test email using the configured mailer (e.g. SendGrid).',
)]
final class SendTestEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string          $from,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Recipient email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = trim((string)$input->getArgument('to'));

        if ($to === '') {
            $io->error('Recipient email is required.');
            return Command::INVALID;
        }

        $email = new Email()
            ->from($this->from)
            ->to($to)
            ->subject('SendGrid test email (AnGo)')
            ->text(sprintf(
                "Hello!\n\nThis is a test email from AnGo.\nTime: %s\n",
                new DateTimeImmutable()->format('c')
            ));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf('Email sent to %s', $to));
        return Command::SUCCESS;
    }
}
