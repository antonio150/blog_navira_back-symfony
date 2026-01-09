<?php

namespace App\Controller\Client\Commentaire;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class UpdateCommentaireClientController
{
    public function __invoke(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
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

        // Sécurité : seul l’auteur peut modifier
        if ($commentaire->getUtilisateur() !== $user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Action non autorisée'
            ], Response::HTTP_FORBIDDEN);
        }

        // Décodage JSON
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Format JSON invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $contenu = trim($data['contenu'] ?? '');
        if ($contenu === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Le champ contenu est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour
        $commentaire->setContenu($contenu);
        $commentaire->setUpdatedAt(new \DateTimeImmutable());

        // Validation
        $errors = $validator->validate($commentaire);
        if (count($errors) > 0) {
            $errorList = [];
            foreach ($errors as $error) {
                $errorList[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ];
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur de validation',
                'errors' => $errorList
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarde
        try {
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Commentaire modifié avec succès',
                'data' => [
                    'id' => $commentaire->getId(),
                    'contenu' => $commentaire->getContenu(),
                    'updatedAt' => $commentaire->getUpdatedAt()->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
