<?php

namespace App\Controller\Client\Realisation;

use App\Entity\Realisation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ListeRealisationClientController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 1000);
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $search = $request->query->get('search', '');
        $technologie = $request->query->get('technologie');

        // Sécurisation
        $page = max(1, $page);
        $limit = min(50, max(1, $limit));

        $validSortFields = ['id', 'contenue', 'createdAt', 'updatedAt', 'titre'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'createdAt';
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        // Requête principale : on revient à la version simple (comme avant ton ajout de count)
        $qb = $this->createQueryBuilder($entityManager, $search, $technologie);

        $qb->orderBy("d.$sortBy", $sortDirection);

        // TOTAL - maintenant ça marche sans problème
        $countQb = clone $qb;
        $totalItems = (int) $countQb
            ->select('COUNT(DISTINCT d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // PAGINATION - realisations sans count
        $realisations = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // === NOUVELLE PARTIE : Récupérer les counts de commentaires séparément ===
        $realisationIds = array_map(fn($doc) => $doc->getId(), $realisations);

        $commentairesCounts = [];
        if (!empty($realisationIds)) {
            $commentairesCounts = $entityManager->createQueryBuilder()
                ->select('d.id AS realisationId')
                ->addSelect('COUNT(c.id) AS commentairesCount')
                ->from(Realisation::class, 'd')
                ->leftJoin('d.commentaires', 'c')
                ->where('d.id IN (:ids)')
                ->setParameter('ids', $realisationIds)
                ->groupBy('d.id')
                ->getQuery()
                ->getArrayResult();

            // Transformation en tableau associatif pour accès rapide : [id => count]
            $commentairesCounts = array_column($commentairesCounts, 'commentairesCount', 'realisationId');
        }

        // Sérialisation + ajout du count
        $data = [];
        foreach ($realisations as $realisation) {
            $serialized = json_decode(
                $serializer->serialize($realisation, 'json', ['groups' => ['realisation:read']]),
                true
            );

            $docId = $realisation->getId();
            $serialized['commentairesCount'] = isset($commentairesCounts[$docId])
                ? (int) $commentairesCounts[$docId]
                : 0;

            $data[] = $serialized;
        }

        return new JsonResponse([
            'status' => 200,
            'message' => 'Liste des realisations',
            'total' => $totalItems,
            'page' => $page,
            'limit' => $limit,
            'data' => $data,
        ]);
    }

    private function createQueryBuilder(
        EntityManagerInterface $entityManager,
        string $search = '',
        ?string $technologie = null
    ): QueryBuilder {
        $qb = $entityManager->createQueryBuilder();

        $qb->select(' d')
            ->from(Realisation::class, 'd');

        // 🔍 Recherche sur titre OU contenu
        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(d.titre)', ':search'),
                    $qb->expr()->like('LOWER(d.contenue)', ':search')
                )
            )
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

      

        return $qb;
    }
}