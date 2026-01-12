<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Google\Client as GoogleClient;

class GoogleAuthController
{
    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        $client = new GoogleClient([
            'client_id' => $_ENV['GOOGLE_CLIENT_ID']
        ]);

        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return new JsonResponse(['error' => 'Token Google invalide'], 401);
        }

        $email = $payload['email'];
        $name = $payload['name'] ?? null;

        $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new Utilisateur();
            $user->setEmail($email);
            $user->setNom($name);
            $user->setAbonnee(true);
            $user->setRoles(['ROLE_USER']);
            $em->persist($user);
            $em->flush();
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'id' => $user->getId(),
            ]
        ]);
    }
}
