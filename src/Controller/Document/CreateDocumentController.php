<?php

namespace App\Controller\Document;

use App\Entity\Document;
use App\Entity\Utilisateur;
use App\Service\MailDocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class CreateDocumentController
{
    

    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        FileUploader $fileUploader,
        MailDocumentService $mailNotification
    ): JsonResponse {
       

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Format JSON invalide'
            ], 400);
        }

        // Champs attendus
        $contenue = $request->request->get('contenue') ?? '';
        $linkTuto = $request->request->get('linkTuto') ?? '';
        $titre = $request->request->get('titre') ?? '';
        $sommaire = $request->request->get('sommaire') ?? '';
        $propos = $request->request->get('propos') ?? '';
        $technologie = $request->request->get('technologie') ?? '';
        $file = $request->files->get('image');

        if ($contenue === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Le champ contenue est requis'
            ], 400);
        }

        // Récupération de l'utilisateur connecté
        $user = $tokenStorage->getToken()?->getUser();
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté pour créer un document'
            ], 401);
        }

        if (!$file) {
                 return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Aucun fichier envoyé'
                ], 401);
             }

            

        $document = new Document();
        $document->setContenue($contenue);
        $document->setLinkTuto($linkTuto);
        $document->setTitre($titre);
        $document->setSommaire($sommaire);
        $document->setPropos($propos);
        $document->setTechnologie($technologie);
        try {
            $result = $fileUploader->upload($file); 
            $publicPath = $result['public_path'];
            $absolutePath = $result['absolute_path'];
            $lienFile = $result["public_path"];

            $document->setPathImageRelative($publicPath);
            $document->setPathImageAbsolue($absolutePath);
        } catch (\Exception $e) {
            return new JsonResponse([
            'status' => 'error',
            'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        
        // Dates automatiques
        $now = new \DateTimeImmutable();
        $document->setCreatedAt($now);
        $document->setUpdatedAt($now);

        // Association avec l'utilisateur connecté
        $document->setCreatedBy($user);

        // Validation des contraintes Doctrine + Validator
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
                'message' => 'Erreur de validation',
                'errors' => $errorList
            ], 400);
        }

        try {
            $entityManager->persist($document);
            $entityManager->flush();

            $utilisateur = $entityManager->getRepository(Utilisateur::class)
             ->findBy(['abonnee' => true]);

            $urlDocument = 'https://blog.antonionavira.mg/home/formation/details/?id=' . $document->getId();
            $titre = $document->getTitre();
           
             if (!empty($utilisateur)) {
                // Envoyer aux commentateurs existants
                foreach ($utilisateur as $utilisateurs) {
                    $unsubscribe_url = 'https://back.blog.antonionavira.mg/api/unsubscribe?email=' . urlencode($utilisateurs->getEmail());
                    $mailNotification->send(
                        $utilisateurs->getEmail(),
                        $utilisateurs->getNom(), 
                        $urlDocument,
                        $titre,
                        $contenue,
                        $document->getId(),
                        $unsubscribe_url
                    );
                }
            } else {
                // Si aucun commentateur existant, envoyer à antoniorollande@gmail.com
                $unsubscribe_url = "";
                $mailNotification->send(
                    'antoniorollande@gmail.com',
                    $user->getUserIdentifier(), // nom de celui qui a commenté
                    $urlDocument,
                    $titre,
                    $contenue,
                    $document->getId(),
                    $unsubscribe_url
                );
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document créé avec succès',
                'data' => [
                    'id'          => $document->getId(),
                    'contenue'    => $document->getContenue(),
                    'createdAt'   => $document->getCreatedAt()->format('Y-m-d H:i:s'),
                    'createdBy'   => $user->getUserIdentifier() // ou getUsername(), getEmail(), etc.
                ]
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la création du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}