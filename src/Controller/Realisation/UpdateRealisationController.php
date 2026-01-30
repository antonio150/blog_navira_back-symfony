<?php

namespace App\Controller\Realisation;

use App\Entity\Realisation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class UpdateRealisationController
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

        // 🔎 Récupération du realisation
        $realisation = $entityManager->getRepository(Realisation::class)->find($id);

        if (!$realisation) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'realisation introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

     

        // Champs reçus
        $contenue = $request->request->get('contenue');
        $lien = $request->request->get('lien');
        $titre = $request->request->get('titre');
        $file     = $request->files->get('image');

        // ✏️ Mise à jour des champs (si fournis)
        if ($contenue !== null) {
            $realisation->setContenue(trim($contenue));
        }

        if ($lien !== null) {
            $realisation->setlien(trim($lien));
        }

        if ($titre !== null) {
            $realisation->setTitre(trim($titre));
        }

        // 🖼️ Upload image (facultatif)
        if ($file) {
            try {
                $result = $fileUploader->upload($file);

                $realisation->setPathImageRelative($result['public_path']);
                $realisation->setPathImageAbsolue($result['absolute_path']);

            } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // ⏱️ Date de mise à jour
        $realisation->setUpdatedAt(new \DateTimeImmutable());

        // ✅ Validation
        $errors = $validator->validate($realisation);
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
                'message' => 'realisation mis à jour avec succès',
                'data' => [
                    'id'        => $realisation->getId(),
                    'contenue'  => $realisation->getContenue(),
                    'updatedAt'=> $realisation->getUpdatedAt()?->format('Y-m-d H:i:s'),
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
