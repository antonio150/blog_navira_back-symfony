<?php

namespace App\Entity;

use App\Repository\ContenuRealisationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContenuRealisationRepository::class)]
class ContenuRealisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['realisation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $titre = null;

    #[ORM\Column(type:'text', nullable: true)]
    #[Groups(['realisation:read'])]
    private ?string $contenue = null;

    #[ORM\ManyToOne(inversedBy: 'contenuRealisations')]
    private ?Realisation $realisation = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContenue(): ?string
    {
        return $this->contenue;
    }

    public function setContenue(?string $contenue): static
    {
        $this->contenue = $contenue;

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
