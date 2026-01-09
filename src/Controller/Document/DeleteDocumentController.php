<?php

namespace App\Controller\Document;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class DeleteDocumentController
{
    public function __invoke(
        int $id,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        FileUploader $fileUploader
    ): JsonResponse {

        // 🔐 Authentification
        $user = $tokenStorage->getToken()?->getUser();
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔎 Récupérer le document
        $document = $entityManager->getRepository(Document::class)->find($id);

        if (!$document) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Document introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        
        // 🖼️ Supprimer l’image du disque
        try {
            if ($document->getPathImageAbsolue()) {
                $this->deletePhysicalFile($document->getPathImageAbsolue());
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l’image',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        // 🗑️ Suppression en base
        try {
            $entityManager->remove($document);
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document supprimé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du document',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function deletePhysicalFile(string $filePath): bool
    {
        $fullPath = str_replace('/uploads/', 'public/uploads/', $filePath);

        if (file_exists($fullPath)) {
            try {
                return unlink($fullPath);
            } catch (\Exception $e) {
                error_log("Erreur lors de la suppression du fichier $fullPath : " . $e->getMessage());
                return false;
            }
        }

        error_log("Le fichier $fullPath n'existe pas.");
        return false;
    }
}
