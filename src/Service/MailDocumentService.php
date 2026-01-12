<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class MailDocumentService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function send(
        ?string $toEmail,
        string $nom,
        string $url,
        string $titre,
        string $contenu,
        int $documentId,
        string $unsubscribe_url
    ): void {
         

        // Si aucun destinataire, on envoie à antoniorollande@gmail.com
        if (!$toEmail) {
            $toEmail = 'antoniorollande@gmail.com';
            $email = (new TemplatedEmail())
                ->from($_ENV['SENDER_MAIL'])
                ->to($toEmail)
                ->subject('Nouvelle sujet publiée #' . $documentId)
                ->htmlTemplate('emails/document.html.twig')
                ;

            $email->embedFromPath(
                __DIR__ . '/../../public/img_default/navira.png',
                'logo_navira'
            );
            $email->context([
                'nom' => $nom,
                'url' => $url,
                'image_url' => 'cid:logo_navira',
                'titre' => $titre,
                'contenu' => $contenu,
                'unsubscribe_url' => $unsubscribe_url
            ]);
        } else {
            // S'il y a déjà un destinataire, on envoie à lui + CC antoniorollande
            $email = (new TemplatedEmail())
                ->from($_ENV['SENDER_MAIL'])
                ->to($toEmail)
                ->cc('antoniorollande@gmail.com')
                ->subject('Nouvelle sujet publiée #' . $documentId)
                ->htmlTemplate('emails/document.html.twig')
                ;

                $email->embedFromPath(
                    __DIR__ . '/../../public/img_default/navira.png',
                    'logo_navira'
                );

                $email->context([
                    'nom' => $nom,
                    'url' => $url,
                    'image_url' => 'cid:logo_navira',
                    'titre' => $titre,
                    'contenu' => $contenu,
                    'unsubscribe_url' => $unsubscribe_url
                ]);
        }

        $this->mailer->send($email);
    }


}
