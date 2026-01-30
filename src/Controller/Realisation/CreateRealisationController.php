<?php 

namespace App\Controller\Realisation;

use App\Entity\Realisation;
use App\Entity\Utilisateur;
use App\Service\MailDocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class CreateRealisationController 
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
        $lien = $request->request->get('lien') ?? '';
        $titre = $request->request->get('titre') ?? '';
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
                'message' => 'Vous devez être connecté pour créer un realisation'
            ], 401);
        }

        if (!$file) {
                 return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Aucun fichier envoyé'
                ], 401);
             }

            

        $realisation = new Realisation();
        $realisation->setContenue($contenue);
        $realisation->setLien($lien);
        $realisation->setTitre($titre);
        try {
            $result = $fileUploader->upload($file); 
            $publicPath = $result['public_path'];
            $absolutePath = $result['absolute_path'];
            $lienFile = $result["public_path"];

            $realisation->setPathImageRelative($publicPath);
            $realisation->setPathImageAbsolue($absolutePath);
        } catch (\Exception $e) {
            return new JsonResponse([
            'status' => 'error',
            'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        
        // Dates automatiques
        $now = new \DateTimeImmutable();
        $realisation->setCreatedAt($now);
        $realisation->setUpdatedAt($now);


        // Validation des contraintes Doctrine + Validator
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
                'message' => 'Erreur de validation',
                'errors' => $errorList
            ], 400);
        }

        try {
            $entityManager->persist($realisation);
            $entityManager->flush();

            $utilisateur = $entityManager->getRepository(Utilisateur::class)
             ->findBy(['abonnee' => true]);

            $urlDocument = 'https://blog.antonionavira.mg/home/formation/details/?id=' . $realisation->getId();
            $titre = $realisation->getTitre();
           
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
                        $realisation->getId(),
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
                    $realisation->getId(),
                    $unsubscribe_url
                );
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document créé avec succès',
                'data' => [
                    'id'          => $realisation->getId(),
                    'contenue'    => $realisation->getContenue(),
                    'createdAt'   => $realisation->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la création du realisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}