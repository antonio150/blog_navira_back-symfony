<?php

namespace App\Controller\Client\Realisation;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ListeCommentaireRealisationClientController
{
    public function __invoke(
        $id_realisation,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        // Récupération de tous les commentaires
        $commentaires = $entityManager
            ->getRepository(Commentaire::class)
            ->findBy(['realisation' => $id_realisation], ['createdAt' => 'DESC']);

        // Sérialisation
        $json = $serializer->serialize($commentaires, 'json', [
            'groups' => ['realisation:read'],
        ]);

        return new JsonResponse([
            'status' => 200,
            'message' => 'Liste des commentaires',
            'data' => json_decode($json, true),
        ]);
    }
}
