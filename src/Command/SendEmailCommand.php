<?php

namespace App\Command;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Envoie un email de test',
)]
class SendEmailCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new TemplatedEmail())
            ->from($_ENV['SENDER_MAIL'])
            ->to('antoniorollande@gmail.com')
            ->subject('Bienvenue sur Navira')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'nom' => 'Antonio',
            ]);

        $this->mailer->send($email);

        $output->writeln('✅ Email envoyé avec succès');

        return Command::SUCCESS;
    }
}
