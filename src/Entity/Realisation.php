<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Client\Realisation\DetailRealisationClientController;
use App\Controller\Client\Realisation\ListeRealisationClientController;
use App\Controller\Realisation\CreateRealisationController;
use App\Controller\Realisation\DeleteRealisationController;
use App\Controller\Realisation\ListeRealisationController;
use App\Controller\Realisation\ShowRealisationController;
use App\Controller\Realisation\UpdateRealisationController;
use App\Repository\RealisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RealisationRepository::class)]
#[ApiResource(
    operations: [
       
        new GetCollection(
            uriTemplate: '/admin/liste_realisation',
            controller: ListeRealisationController::class
        ),
        new GetCollection(
            uriTemplate: '/client/realisation/liste_realisation',
            controller: ListeRealisationClientController::class
        ),
        new Post(
            uriTemplate: '/admin/create_realisation',
            controller: CreateRealisationController::class
        ),  
        
        new Post(
            uriTemplate: '/admin/update_realisation/{id}',
            controller: UpdateRealisationController::class
        ),    
        new Delete(
            uriTemplate: '/admin/delete_realisation/{id}',
            controller: DeleteRealisationController::class
        ),
        new Get(
            uriTemplate: '/admin/show_realisation/{id}',
            controller: ShowRealisationController::class
        ),
        new Get(
            uriTemplate: '/client/detail_realisation/{id}',
            controller: DetailRealisationClientController::class
        )
 
    ],
    normalizationContext: ['groups' => ['realisation:read']],
    denormalizationContext: ['groups' => ['realisation:write']]
)]
class Realisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['realisation:read'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['realisation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['realisation:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $contenue = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $pathImageAbsolue = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $pathImageRelative = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $lien = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'realisation')]
    #[Groups(['realisation:read'])]
    private Collection $commentaires;

    /**
     * @var Collection<int, ContenuRealisation>
     */
    #[ORM\OneToMany(targetEntity: ContenuRealisation::class, mappedBy: 'realisation')]
    #[Groups(['realisation:read'])]
    private Collection $contenuRealisations;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->contenuRealisations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContenue(): ?string
    {
        return $this->contenue;
    }

    public function setContenue(?string $contenue): static
    {
        $this->contenue = $contenue;

        return $this;
    }

    public function gettitre(): ?string
    {
        return $this->titre;
    }

    public function settitre(?string $titre): static
    {
        $this->titre = $titre;

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

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(?string $lien): static
    {
        $this->lien = $lien;

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
            $commentaire->setRealisation($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getRealisation() === $this) {
                $commentaire->setRealisation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContenuRealisation>
     */
    public function getContenuRealisations(): Collection
    {
        return $this->contenuRealisations;
    }

    public function addContenuRealisation(ContenuRealisation $contenuRealisation): static
    {
        if (!$this->contenuRealisations->contains($contenuRealisation)) {
            $this->contenuRealisations->add($contenuRealisation);
            $contenuRealisation->setRealisation($this);
        }

        return $this;
    }

    public function removeContenuRealisation(ContenuRealisation $contenuRealisation): static
    {
        if ($this->contenuRealisations->removeElement($contenuRealisation)) {
            // set the owning side to null (unless already changed)
            if ($contenuRealisation->getRealisation() === $this) {
                $contenuRealisation->setRealisation(null);
            }
        }

        return $this;
    }
}
