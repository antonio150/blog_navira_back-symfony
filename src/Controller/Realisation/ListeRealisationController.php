<?php 
namespace App\Controller\Realisation;

use App\Entity\Document;
use App\Entity\Realisation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use UploadImageBundle\Service\FileUploader;

#[AsController]
class ListeRealisationController
{
    public function __invoke(Request $request, 
    EntityManagerInterface $entityManager,
    SerializerInterface $serializer,
    ): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $search = $request->query->get('search', '');

        // Validation des paramètres
        $page = max(1, $page);
        $limit = min(50, max(1, $limit)); // Limite max 50 pour perf
        $validSortFields = ['id', 'contenue', 'createdAt', 'updatedAt'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'createdAt';
        $sortDirection = in_array(strtoupper($sortDirection), ['ASC', 'DESC']) ? $sortDirection : 'DESC';

        $qb = $this->createQueryBuilder($entityManager, $search);
        
        // Application du tri
        $qb->orderBy("d.{$sortBy}", $sortDirection);
        
        $countQb = clone $qb;
        $totalItems = (int) $countQb
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // PAGINATION
        $realisations = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $json = $serializer->serialize($realisations, 'json', [
            'groups' => ['realisation:read']
        ]);
        return new JsonResponse([
            'status' => 200,
            'message' => 'Liste des realisations',
            'data' => json_decode($json, true),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalItems' => $totalItems,
                'hasPrevious' => $page > 1,
            ],
            'filters' => [
                'search' => $search,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ]
        ]);
    }

    private function createQueryBuilder(EntityManagerInterface $entityManager, string $search): QueryBuilder
    {
        $qb = $entityManager->createQueryBuilder();
        $qb->select('d')
           ->from(Realisation::class, 'd')
           ->leftJoin('d.commentaires', 'c');

        // Recherche full-text sur contenue
        if (!empty($search)) {
            $searchLike = '%' . $search . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('d.contenue', ':search')
                )
            )->setParameter('search', $searchLike);
        }

        return $qb;
    }
}