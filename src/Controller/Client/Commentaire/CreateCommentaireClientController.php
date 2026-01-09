<?php

namespace App\Controller\Client\Commentaire;

use App\Entity\Commentaire;
use App\Entity\Document;
use App\Service\MailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class CreateCommentaireClientController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage,
        MailNotificationService $mailNotification
    ): JsonResponse {

        // Décodage JSON
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Format JSON invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Champs requis
        $contenu = trim($data['contenu'] ?? '');
        $documentId = $data['document_id'] ?? null;

        if ($contenu === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Le champ contenu est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$documentId) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Document invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Utilisateur connecté
        $user = $tokenStorage->getToken()?->getUser();
        if (!$user || !is_object($user)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté pour commenter'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérification du document
        $document = $entityManager->getRepository(Document::class)->find($documentId);
        if (!$document) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Document introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Création du commentaire
        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setUtilisateur($user);
        $commentaire->setDocument($document);

        $now = new \DateTimeImmutable();
        $commentaire->setCreatedAt($now);
        $commentaire->setUpdatedAt($now);

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
            $entityManager->persist($commentaire);
            $entityManager->flush();

            // Récupérer tous les utilisateurs qui ont commenté ce document
            $commentateurs = $entityManager->getRepository(Commentaire::class)
                ->createQueryBuilder('c')
                ->select('u.email, u.nom')
                ->join('c.utilisateur', 'u')
                ->where('c.document = :document')
                ->andWhere('u != :currentUser') // éviter l'utilisateur qui vient de commenter
                ->setParameter('document', $document)
                ->setParameter('currentUser', $user)
                ->distinct()
                ->getQuery()
                ->getArrayResult();

            $urlDocument = 'https://blog.antonionavira.mg/home/formation/details/?id=' . $document->getId();
            $titre = $document->getTitre();

            if (!empty($commentateurs)) {
                // Envoyer aux commentateurs existants
                foreach ($commentateurs as $destinataire) {
                    $mailNotification->send(
                        $destinataire['email'],
                        $destinataire['nom'], // nom de celui qui a commenté
                        $urlDocument,
                        $titre
                    );
                }
            } else {
                // Si aucun commentateur existant, envoyer à antoniorollande@gmail.com
                $mailNotification->send(
                    'antoniorollande@gmail.com',
                    $user->getUserIdentifier(), // nom de celui qui a commenté
                    $urlDocument,
                    $titre
                );
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Commentaire ajouté avec succès',
                'data' => [
                    'id' => $commentaire->getId(),
                    'contenu' => $commentaire->getContenu(),
                    'createdAt' => $commentaire->getCreatedAt()->format('Y-m-d H:i:s'),
                    'utilisateur' => $user->getUserIdentifier(),
                    'document_id' => $document->getId()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la création du commentaire',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
