<?php

namespace App\Controller\Document;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class UpdateDocumentController
{
    public function __invoke(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        FileUploader $fileUploader
    ): JsonResponse {

        // 🔐 Vérifier utilisateur connecté
        $user = $tokenStorage->getToken()?->getUser();
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔎 Récupération du document
        $document = $entityManager->getRepository(Document::class)->find($id);

        if (!$document) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Document introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // 🔒 Optionnel : sécurité (propriétaire)
        if ($document->getCreatedBy() !== $user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Accès refusé'
            ], Response::HTTP_FORBIDDEN);
        }

        // Champs reçus
        $contenue = $request->request->get('contenue');
        $linkTuto = $request->request->get('linkTuto');
        $titre = $request->request->get('titre');
        $sommaire = $request->request->get('sommaire');
        $propos = $request->request->get('propos');
        $technologie = $request->request->get('technologie');
        $file     = $request->files->get('image');

        // ✏️ Mise à jour des champs (si fournis)
        if ($contenue !== null) {
            $document->setContenue(trim($contenue));
        }

        if ($linkTuto !== null) {
            $document->setLinkTuto(trim($linkTuto));
        }

        if ($titre !== null) {
            $document->setTitre(trim($titre));
        }

        if ($sommaire !== null) {
            $document->setSommaire(trim($sommaire));
        }

        if ($propos !== null) {
            $document->setPropos(trim($propos));
        }

        if ($technologie !== null) {
            $document->setTechnologie(trim($technologie));
        }

        // 🖼️ Upload image (facultatif)
        if ($file) {
            try {
                $result = $fileUploader->upload($file);

                $document->setPathImageRelative($result['public_path']);
                $document->setPathImageAbsolue($result['absolute_path']);

            } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // ⏱️ Date de mise à jour
        $document->setUpdatedAt(new \DateTimeImmutable());

        // ✅ Validation
        $errors = $validator->validate($document);
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
                'errors' => $errorList
            ], Response::HTTP_BAD_REQUEST);
        }

        // 💾 Sauvegarde
        try {
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document mis à jour avec succès',
                'data' => [
                    'id'        => $document->getId(),
                    'contenue'  => $document->getContenue(),
                    'updatedAt'=> $document->getUpdatedAt()?->format('Y-m-d H:i:s'),
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
