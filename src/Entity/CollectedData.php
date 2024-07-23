<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\CollectedDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectedDataRepository::class)]
class CollectedData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $countNumber = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $totalCount = null;

    /**
     * @var Collection<int, BirdSpecies>
     */
    #[ORM\ManyToMany(targetEntity: BirdSpecies::class, inversedBy: 'collectedData')]
    private Collection $birdSpecies;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CountingCampaign $countingCampaign = null;

    #[ORM\OneToOne(mappedBy: 'collectedData', cascade: ['persist', 'remove'])]
    private ?EnvironmentalConditions $environmentalConditions = null;

    public function __construct()
    {
        $this->birdSpecies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountNumber(): ?int
    {
        return $this->countNumber;
    }

    public function setCountNumber(?int $countNumber): static
    {
        $this->countNumber = $countNumber;

        return $this;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function setTotalCount(?int $totalCount): static
    {
        $this->totalCount = $totalCount;

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
        }

        return $this;
    }

    public function removeBirdSpecies(BirdSpecies $birdSpecies): static
    {
        $this->birdSpecies->removeElement($birdSpecies);

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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCountingCampaign(): ?CountingCampaign
    {
        return $this->countingCampaign;
    }

    public function setCountingCampaign(?CountingCampaign $countingCampaign): static
    {
        $this->countingCampaign = $countingCampaign;

        return $this;
    }

    public function getEnvironmentalConditions(): ?EnvironmentalConditions
    {
        return $this->environmentalConditions;
    }

    public function setEnvironmentalConditions(EnvironmentalConditions $environmentalConditions): static
    {
        // set the owning side of the relation if necessary
        if ($environmentalConditions->getCollectedData() !== $this) {
            $environmentalConditions->setCollectedData($this);
        }

        $this->environmentalConditions = $environmentalConditions;

        return $this;
    }
}
