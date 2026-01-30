<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Client\Commentaire\CreateCommentaireClientController;
use App\Controller\Client\Commentaire\DeleteCommentaireClientController;
use App\Controller\Client\Commentaire\ListeCommentaireClientController;
use App\Controller\Client\Commentaire\UpdateCommentaireClientController;
use App\Controller\Client\Realisation\CreateCommentaireRealisationClientController;
use App\Controller\Client\Realisation\DeleteCommentaireRealisationClientController;
use App\Controller\Client\Realisation\ListeCommentaireRealisationClientController;
use App\Controller\Client\Realisation\UpdateCommentaireRealisationClientController;
use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ApiResource(
    operations: [
       
        new GetCollection(
            uriTemplate: '/client/commentaire/liste_commentaire/{id_document}',
            controller: ListeCommentaireClientController::class
        ),
        
        new Post(
            uriTemplate: '/client/commentaire/create_commentaire',
            controller: CreateCommentaireClientController::class
        ),
        new Post(
            uriTemplate: '/client/commentaire/update_commentaire/{id}',
            controller: UpdateCommentaireClientController::class
        ),
        new Delete( 
            uriTemplate: '/client/commentaire/delete_commentaire/{id}',
            controller: DeleteCommentaireClientController::class
        ),

        new GetCollection(
            uriTemplate: '/client/commentaire/realisation/liste_commentaire/{id_realisation}',
            controller: ListeCommentaireRealisationClientController::class
        ),
        
        new Post(
            uriTemplate: '/client/commentaire/realisation/create_commentaire',
            controller: CreateCommentaireRealisationClientController::class
        ),
        new Post(
            uriTemplate: '/client/commentaire/realisation/update_commentaire/{id}',
            controller: UpdateCommentaireRealisationClientController::class
        ),
        new Delete( 
            uriTemplate: '/client/commentaire/realisation/delete_commentaire/{id}',
            controller: DeleteCommentaireRealisationClientController::class
        ),
    ],
)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commentaire:read','realisation:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['commentaire:read','realisation:read'])]
    private ?string $contenu = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['commentaire:read','realisation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['commentaire:read','realisation:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[Groups(['commentaire:read','realisation:read'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[Groups(['commentaire:read'])]
    private ?Document $document = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[Groups(['realisation:read'])]
    private ?Realisation $realisation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getRealisation(): ?Realisation
    {
        return $this->realisation;
    }

    public function setRealisation(?Realisation $realisation): static
    {
        $this->realisation = $realisation;

        return $this;
    }
}
