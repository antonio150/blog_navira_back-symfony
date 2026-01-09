<?php

namespace App\Controller\Document;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ShowDocumentController
{
    public function __invoke(
        int $id,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        // Récupération du document
        $document = $entityManager->getRepository(Document::class)->find($id);

        if (!$document) {
            return new JsonResponse([
                'status' => 404,
                'message' => 'Document non trouvé'
            ], 404);
        }

        $json = $serializer->serialize($document, 'json', [
            'groups' => ['document:read']
        ]);

        return new JsonResponse([
            'status' => 200,
            'message' => 'Document trouvé',
            'data' => json_decode($json, true)
        ]);
    }
}
