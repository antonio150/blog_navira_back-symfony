<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class UtilisateurAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UtilisateurRepository $utilisateurRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/login'
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $email    = $data['email']    ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            throw new AuthenticationException('Email et mot de passe sont obligatoires');
        }

        return new Passport(
            new UserBadge($email, function (string $userIdentifier): Utilisateur {
                $user = $this->utilisateurRepository->findOneBy([
                    'email' => $userIdentifier
                ]);

                if (!$user) {
                    throw new AuthenticationException('Utilisateur introuvable');
                }

                return $user;
            }),
            new PasswordCredentials($password)
        );

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            throw new \LogicException('Utilisateur attendu');
        }

        $jwt = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $jwt,
            'user' => [
                'id'     => $user->getId(),
                'email'  => $user->getEmail(),
                'nom'    => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles'  => $user->getRoles()
            ],
            'message' => 'Connexion réussie'
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey()
                ? strtr($exception->getMessageKey(), $exception->getMessageData())
                : 'Identifiants invalides'
        ], Response::HTTP_UNAUTHORIZED);
    }
}