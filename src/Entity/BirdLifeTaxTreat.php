<?php

namespace App\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use App\Repository\BirdLifeTaxTreatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[UniqueEntity(fields: ['label'], message: 'Il existe déjà')]
#[ORM\Entity(repositoryClass: BirdLifeTaxTreatRepository::class)]
class BirdLifeTaxTreat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, BirdSpecies>
     */
    #[ORM\OneToMany(targetEntity: BirdSpecies::class, mappedBy: 'birdLifeTaxTreat')]
    private Collection $birdLifeTaxTreat;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->birdLifeTaxTreat = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, BirdSpecies>
     */
    public function getBirdLifeTaxTreat(): Collection
    {
        return $this->birdLifeTaxTreat;
    }

    public function addBirdLifeTaxTreat(BirdSpecies $birdLifeTaxTreat): static
    {
        if (!$this->birdLifeTaxTreat->contains($birdLifeTaxTreat)) {
            $this->birdLifeTaxTreat->add($birdLifeTaxTreat);
            $birdLifeTaxTreat->setBirdLifeTaxTreat($this);
        }

        return $this;
    }

    public function removeBirdLifeTaxTreat(BirdSpecies $birdLifeTaxTreat): static
    {
        if ($this->birdLifeTaxTreat->removeElement($birdLifeTaxTreat)) {
            // set the owning side to null (unless already changed)
            if ($birdLifeTaxTreat->getBirdLifeTaxTreat() === $this) {
                $birdLifeTaxTreat->setBirdLifeTaxTreat(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel() ?: '';
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
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
}
