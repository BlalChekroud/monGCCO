<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
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

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SiteCollection $siteCollection = null;

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CountType $countType = null;

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quality $quality = null;

    /**
     * @var Collection<int, Method>
     */
    #[ORM\ManyToMany(targetEntity: Method::class, inversedBy: 'collectedData')]
    #[Assert\NotBlank(message: "Veuillez sélectionner au moins une méthode.")]
    private Collection $method;

    /**
     * @var Collection<int, BirdSpeciesCount>
     */
    #[ORM\OneToMany(targetEntity: BirdSpeciesCount::class, mappedBy: 'collectedData', orphanRemoval: true)]
    private Collection $birdSpeciesCounts;

    public function __construct()
    {
        $this->birdSpecies = new ArrayCollection();
        $this->method = new ArrayCollection();
        $this->birdSpeciesCounts = new ArrayCollection();
    }

    public function getTotalCount(): int
    {
        $totalCount = 0;
        
        foreach ($this->getBirdSpeciesCounts() as $birdSpeciesCount) {
            $totalCount += $birdSpeciesCount->getCount();
        }

        return $totalCount;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSiteCollection(): ?SiteCollection
    {
        return $this->siteCollection;
    }

    public function setSiteCollection(?SiteCollection $siteCollection): static
    {
        $this->siteCollection = $siteCollection;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCountType(): ?CountType
    {
        return $this->countType;
    }

    public function setCountType(?CountType $countType): static
    {
        $this->countType = $countType;

        return $this;
    }

    public function getQuality(): ?Quality
    {
        return $this->quality;
    }

    public function setQuality(?Quality $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @return Collection<int, Method>
     */
    public function getMethod(): Collection
    {
        return $this->method;
    }

    public function addMethod(Method $method): static
    {
        if (!$this->method->contains($method)) {
            $this->method->add($method);
        }

        return $this;
    }

    public function removeMethod(Method $method): static
    {
        $this->method->removeElement($method);

        return $this;
    }

    /**
     * @return Collection<int, BirdSpeciesCount>
     */
    public function getBirdSpeciesCounts(): Collection
    {
        return $this->birdSpeciesCounts;
    }

    public function addBirdSpeciesCount(BirdSpeciesCount $birdSpeciesCount): static
    {
        if (!$this->birdSpeciesCounts->contains($birdSpeciesCount)) {
            $this->birdSpeciesCounts->add($birdSpeciesCount);
            $birdSpeciesCount->setCollectedData($this);
        }

        return $this;
    }

    public function removeBirdSpeciesCount(BirdSpeciesCount $birdSpeciesCount): static
    {
        if ($this->birdSpeciesCounts->removeElement($birdSpeciesCount)) {
            // set the owning side to null (unless already changed)
            if ($birdSpeciesCount->getCollectedData() === $this) {
                $birdSpeciesCount->setCollectedData(null);
            }
        }

        return $this;
    }
}
