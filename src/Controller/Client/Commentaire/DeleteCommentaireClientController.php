<?php

namespace App\Controller\Client\Commentaire;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
class DeleteCommentaireClientController
{
    public function __invoke(
        int $id,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {

        // Utilisateur connecté
        $user = $tokenStorage->getToken()?->getUser();
        if (!$user || !is_object($user)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Commentaire
        $commentaire = $entityManager->getRepository(Commentaire::class)->find($id);
        if (!$commentaire) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Commentaire introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Sécurité : seul l’auteur peut supprimer
        if ($commentaire->getUtilisateur() !== $user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Action non autorisée'
            ], Response::HTTP_FORBIDDEN);
        }

        // Suppression
        try {
            $entityManager->remove($commentaire);
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Commentaire supprimé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
