<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\Client\Document\DetailDocumentClientController;
use App\Controller\Document\CreateDocumentController;
use App\Controller\Document\ListeDocumentController;
use App\Controller\Document\DeleteDocumentController;
use App\Controller\Client\Document\ListeDocumentClientController;
use App\Controller\Document\ShowDocumentController;
use App\Controller\Document\UpdateDocumentController;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ApiResource(
    operations: [
       
        new GetCollection(
            uriTemplate: '/admin/liste_document',
            controller: ListeDocumentController::class
        ),
        new GetCollection(
            uriTemplate: '/client/document/liste_document',
            controller: ListeDocumentClientController::class
        ),
        new Post(
            uriTemplate: '/admin/create_document',
            controller: CreateDocumentController::class
        ),  
        
        new Post(
            uriTemplate: '/admin/update_document/{id}',
            controller: UpdateDocumentController::class
        ),    
        new Delete(
            uriTemplate: '/admin/delete_document/{id}',
            controller: DeleteDocumentController::class
        ),
        new Get(
            uriTemplate: '/admin/show_document/{id}',
            controller: ShowDocumentController::class
        ),
        new Get(
            uriTemplate: '/client/detail_document/{id}',
            controller: DetailDocumentClientController::class
        )
 
    ],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $contenue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $sommaire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $propos = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $pathImageAbsolue = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $pathImageRelative = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $linkTuto = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $technologie = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'document')]
    #[Groups(['document:read'])]
    private Collection $commentaires;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true)] 
    #[Groups(['document:read'])]  
    private ?Utilisateur $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])] 
    private ?string $titre = null;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenue(): ?string
    {
        return $this->contenue;
    }

    public function setContenue(?string $contenue): static
    {
        $this->contenue = $contenue;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setDocument($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getDocument() === $this) {
                $commentaire->setDocument(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?Utilisateur
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Utilisateur $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getPathImageAbsolue(): ?string
    {
        return $this->pathImageAbsolue;
    }

    public function setPathImageAbsolue(?string $pathImageAbsolue): static
    {
        $this->pathImageAbsolue = $pathImageAbsolue;

        return $this;
    }

    public function getPathImageRelative(): ?string
    {
        return $this->pathImageRelative;
    }

    public function setPathImageRelative(?string $pathImageRelative): static
    {
        $this->pathImageRelative = $pathImageRelative;

        return $this;
    }

    public function getLinkTuto(): ?string
    {
        return $this->linkTuto;
    }

    public function setLinkTuto(?string $linkTuto): static
    {
        $this->linkTuto = $linkTuto;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSommaire(): ?string
    {
        return $this->sommaire;
    }

    public function setSommaire(?string $sommaire): static
    {
        $this->sommaire = $sommaire;

        return $this;
    }

    public function getPropos(): ?string
    {
        return $this->propos;
    }

    public function setPropos(?string $propos): static
    {
        $this->propos = $propos;

        return $this;
    }

    public function getTechnologie(): ?string
    {
        return $this->technologie;
    }

    public function setTechnologie(?string $technologie): static
    {
        $this->technologie = $technologie;

        return $this;
    }

}
