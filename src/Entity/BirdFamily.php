<?php

namespace App\Entity;

use App\Repository\BirdFamilyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BirdFamilyRepository::class)]
class BirdFamily
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $familyName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subFamily = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $tribe = null;

    /**
     * @var Collection<int, BirdSpecies>
     */
    #[ORM\OneToMany(targetEntity: BirdSpecies::class, mappedBy: 'birdFamily')]
    private Collection $birdSpecies;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->birdSpecies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): static
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getSubFamily(): ?string
    {
        return $this->subFamily;
    }

    public function setSubFamily(?string $subFamily): static
    {
        $this->subFamily = $subFamily;

        return $this;
    }

    public function getTribe(): ?string
    {
        return $this->tribe;
    }

    public function setTribe(?string $tribe): static
    {
        $this->tribe = $tribe;

        return $this;
    }

    /**
     * @return Collection<int, BirdSpecies>
     */
    public function getBirdSpecies(): Collection
    {
        return $this->birdSpecies;
    }

    public function addBirdSpecies(BirdSpecies $birdSpecies): static
    {
        if (!$this->birdSpecies->contains($birdSpecies)) {
            $this->birdSpecies->add($birdSpecies);
            $birdSpecies->setBirdFamily($this);
        }

        return $this;
    }

    public function removeBirdSpecies(BirdSpecies $birdSpecies): static
    {
        if ($this->birdSpecies->removeElement($birdSpecies)) {
            // set the owning side to null (unless already changed)
            if ($birdSpecies->getBirdFamily() === $this) {
                $birdSpecies->setBirdFamily(null);
            }
        }

        return $this;
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
