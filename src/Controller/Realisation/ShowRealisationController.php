<?php

namespace App\Controller\Realisation;

use App\Entity\Realisation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ShowRealisationController
{
    public function __invoke(
        int $id,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        // Récupération du realisation
        $realisation = $entityManager->getRepository(Realisation::class)->find($id);

        if (!$realisation) {
            return new JsonResponse([
                'status' => 404,
                'message' => 'realisation non trouvé'
            ], 404);
        }

        $json = $serializer->serialize($realisation, 'json', [
            'groups' => ['realisation:read']
        ]);

        return new JsonResponse([
            'status' => 200,
            'message' => 'realisation trouvé',
            'data' => json_decode($json, true)
        ]);
    }
}
