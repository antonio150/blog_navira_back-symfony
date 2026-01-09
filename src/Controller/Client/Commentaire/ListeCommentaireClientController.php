<?php

namespace App\Controller\Client\Commentaire;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ListeCommentaireClientController
{
    public function __invoke(
        $id_document,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        // Récupération de tous les commentaires
        $commentaires = $entityManager
            ->getRepository(Commentaire::class)
            ->findBy(['document' => $id_document], ['createdAt' => 'DESC']);

        // Sérialisation
        $json = $serializer->serialize($commentaires, 'json', [
            'groups' => ['commentaire:read'],
        ]);

        return new JsonResponse([
            'status' => 200,
            'message' => 'Liste des commentaires',
            'data' => json_decode($json, true),
        ]);
    }
}
