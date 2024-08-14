<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\BirdSpeciesCountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BirdSpeciesCountRepository::class)]
class BirdSpeciesCount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?int $count = null;

    #[ORM\ManyToOne(inversedBy: 'birdSpeciesCounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CollectedData $collectedData = null;

    #[ORM\ManyToOne(inversedBy: 'birdSpeciesCounts')]
    private ?BirdSpecies $birdSpecies = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getCollectedData(): ?CollectedData
    {
        return $this->collectedData;
    }

    public function setCollectedData(?CollectedData $collectedData): static
    {
        $this->collectedData = $collectedData;

        return $this;
    }

    public function getBirdSpecies(): ?BirdSpecies
    {
        return $this->birdSpecies;
    }

    public function setBirdSpecies(?BirdSpecies $birdSpecies): static
    {
        $this->birdSpecies = $birdSpecies;

        return $this;
    }
}
