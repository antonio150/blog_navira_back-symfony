<?php

namespace App\Controller\Client\Realisation;

use App\Entity\Commentaire;
use App\Entity\Realisation;
use App\Service\MailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class CreateCommentaireRealisationClientController
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
        $realisationId = $data['realisation_id'] ?? null;

        if ($contenu === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Le champ contenu est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$realisationId) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Realisation invalide'
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

        // Vérification du realisation
        $realisation = $entityManager->getRepository(Realisation::class)->find($realisationId);
        if (!$realisation) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Realisation introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Création du commentaire
        $commentaire = new Commentaire();
        $commentaire->setContenu($contenu);
        $commentaire->setUtilisateur($user);
        $commentaire->setRealisation($realisation);

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

            // Récupérer tous les utilisateurs qui ont commenté ce realisation
            $commentateurs = $entityManager->getRepository(Commentaire::class)
                ->createQueryBuilder('c')
                ->select('u.email, u.nom')
                ->join('c.utilisateur', 'u')
                ->where('c.realisation = :realisation')
                ->andWhere('u != :currentUser') 
                ->setParameter('realisation', $realisation)
                ->setParameter('currentUser', $user)
                ->distinct()
                ->getQuery()
                ->getArrayResult();

            $urlrealisation = 'https://blog.antonionavira.mg/home/formation/details/?id=' . $realisation->getId();
            $titre = $realisation->getTitre();

            if (!empty($commentateurs)) {
                // Envoyer aux commentateurs existants
                foreach ($commentateurs as $destinataire) {
                    $mailNotification->send(
                        $destinataire['email'],
                        $destinataire['nom'], // nom de celui qui a commenté
                        $urlrealisation,
                        $titre
                    );
                }
            } else {
                // Si aucun commentateur existant, envoyer à antoniorollande@gmail.com
                $mailNotification->send(
                    'antoniorollande@gmail.com',
                    $user->getUserIdentifier(), // nom de celui qui a commenté
                    $urlrealisation,
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
                    'realisation_id' => $realisation->getId()
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
