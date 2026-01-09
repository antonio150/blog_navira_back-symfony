<?php

namespace App\Controller\Client\Document;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class ListeDocumentClientController
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

        // PAGINATION - documents sans count
        $documents = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // === NOUVELLE PARTIE : Récupérer les counts de commentaires séparément ===
        $documentIds = array_map(fn($doc) => $doc->getId(), $documents);

        $commentairesCounts = [];
        if (!empty($documentIds)) {
            $commentairesCounts = $entityManager->createQueryBuilder()
                ->select('d.id AS documentId')
                ->addSelect('COUNT(c.id) AS commentairesCount')
                ->from(Document::class, 'd')
                ->leftJoin('d.commentaires', 'c')
                ->where('d.id IN (:ids)')
                ->setParameter('ids', $documentIds)
                ->groupBy('d.id')
                ->getQuery()
                ->getArrayResult();

            // Transformation en tableau associatif pour accès rapide : [id => count]
            $commentairesCounts = array_column($commentairesCounts, 'commentairesCount', 'documentId');
        }

        // Sérialisation + ajout du count
        $data = [];
        foreach ($documents as $document) {
            $serialized = json_decode(
                $serializer->serialize($document, 'json', ['groups' => ['document:read']]),
                true
            );

            $docId = $document->getId();
            $serialized['commentairesCount'] = isset($commentairesCounts[$docId])
                ? (int) $commentairesCounts[$docId]
                : 0;

            $data[] = $serialized;
        }

        return new JsonResponse([
            'status' => 200,
            'message' => 'Liste des documents',
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
            ->from(Document::class, 'd')
            ->leftJoin('d.createdBy', 'u');

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

        // 🧪 Filtre par technologie
        if (!empty($technologie)) {
            $technos = array_map('trim', explode(',', $technologie));

            $orX = $qb->expr()->orX();

            foreach ($technos as $index => $techno) {
                $param = 'techno_' . $index;
                $orX->add($qb->expr()->like('LOWER(d.technologie)', ':' . $param));
                $qb->setParameter($param, '%' . mb_strtolower($techno) . '%');
            }

            $qb->andWhere($orX);
        }

        return $qb;
    }
}