<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class UnsubscribeController extends AbstractController
{
    #[Route('/api/unsubscribe', name: 'app_unsubscribe', methods: ['GET'])]
    public function unsubscribe(
        Request $request,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $email = $request->query->get('email');

        if (!$email) {
            return new Response('Lien invalide.', 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new Response('Utilisateur introuvable.', 404);
        }

        // On met isSubscribed à false
        $user->setAbonnee(false);

        $em->flush();

        return $this->render('unsubscribe/confirmed.html.twig', [
            'email' => $email
        ]);
    }
}
