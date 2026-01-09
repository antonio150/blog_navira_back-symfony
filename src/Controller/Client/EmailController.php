<?php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Attribute\Route;

class EmailController extends AbstractController
{
    #[Route('/api/mail', name: 'api_mail', methods: ['POST'])]
    public function sendMail(MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from('admin@antonionavira.mg')
            ->to('antoniorollande@gmail.com')
            ->subject('Bienvenue sur Navira')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'nom' => 'Antonio',
            ]);

        $mailer->send($email);
    }
}
