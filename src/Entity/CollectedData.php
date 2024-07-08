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

    // #[ORM\Column(nullable: true)]
    // private ?int $countNumber = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $countType = null;

    #[ORM\Column(nullable: true)]
    private ?int $quality = null;

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

    #[ORM\Column(length: 1)]
    private ?string $disturbed = null;

    #[ORM\Column(length: 1)]
    private ?string $weather = null;

    #[ORM\Column(length: 1)]
    private ?string $water = null;

    #[ORM\Column(length: 1)]
    private ?string $ice = null;

    #[ORM\Column(length: 1)]
    private ?string $tidal = null;

    #[ORM\ManyToOne(inversedBy: 'collectedData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CountingCampaign $countingCampaign = null;

    #[ORM\Column(type: Types::JSON)]
    private array $method = [];

    // #[ORM\ManyToOne(inversedBy: 'collectedData')]
    // #[ORM\JoinColumn(nullable: true)]
    // private ?SiteCollection $siteCollection = null;

    public function __construct()
    {
        $this->birdSpecies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // public function getCountNumber(): ?int
    // {
    //     return $this->countNumber;
    // }

    // public function setCountNumber(?int $countNumber): static
    // {
    //     $this->countNumber = $countNumber;

    //     return $this;
    // }

    public function getCountType(): ?string
    {
        return $this->countType;
    }

    public function setCountType(?string $countType): static
    {
        $this->countType = $countType;

        return $this;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function setQuality(?int $quality): static
    {
        $this->quality = $quality;

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

    public function getDisturbed(): ?string
    {
        return $this->disturbed;
    }

    public function setDisturbed(string $disturbed): static
    {
        $this->disturbed = $disturbed;

        return $this;
    }

    public function getWeather(): ?string
    {
        return $this->weather;
    }

    public function setWeather(string $weather): static
    {
        $this->weather = $weather;

        return $this;
    }

    public function getWater(): ?string
    {
        return $this->water;
    }

    public function setWater(string $water): static
    {
        $this->water = $water;

        return $this;
    }

    public function getIce(): ?string
    {
        return $this->ice;
    }

    public function setIce(string $ice): static
    {
        $this->ice = $ice;

        return $this;
    }

    public function getTidal(): ?string
    {
        return $this->tidal;
    }

    public function setTidal(string $tidal): static
    {
        $this->tidal = $tidal;

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

    public function getMethod(): array
    {
        return $this->method;
    }

    public function setMethod(array $method): static
    {
        $this->method = $method;

        return $this;
    }

    // public function getSiteCollection(): ?SiteCollection
    // {
    //     return $this->siteCollection;
    // }

    // public function setSiteCollection(?SiteCollection $siteCollection): static
    // {
    //     $this->siteCollection = $siteCollection;

    //     return $this;
    // }
}
