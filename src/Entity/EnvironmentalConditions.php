<?php

namespace App\Entity;

use App\Repository\EnvironmentalConditionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnvironmentalConditionsRepository::class)]
class EnvironmentalConditions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Column(length: 255)]
    // private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Disturbed $disturbed = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ice $ice = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tidal $tidal = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Water $water = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Weather $weather = null;

    #[ORM\OneToOne(inversedBy: 'environmentalConditions', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?CollectedData $collectedData = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SiteCollection $siteCollection = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CountingCampaign $countingCampaign = null;

    #[ORM\ManyToOne(inversedBy: 'environmentalConditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // public function getName(): ?string
    // {
    //     return $this->name;
    // }

    // public function setName(string $name): static
    // {
    //     $this->name = $name;

    //     return $this;
    // }

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

    public function getDisturbed(): ?Disturbed
    {
        return $this->disturbed;
    }

    public function setDisturbed(?Disturbed $disturbed): static
    {
        $this->disturbed = $disturbed;

        return $this;
    }

    public function getIce(): ?Ice
    {
        return $this->ice;
    }

    public function setIce(?Ice $ice): static
    {
        $this->ice = $ice;

        return $this;
    }

    public function getTidal(): ?Tidal
    {
        return $this->tidal;
    }

    public function setTidal(?Tidal $tidal): static
    {
        $this->tidal = $tidal;

        return $this;
    }

    public function getWater(): ?Water
    {
        return $this->water;
    }

    public function setWater(?Water $water): static
    {
        $this->water = $water;

        return $this;
    }

    public function getWeather(): ?Weather
    {
        return $this->weather;
    }

    public function setWeather(?Weather $weather): static
    {
        $this->weather = $weather;

        return $this;
    }

    public function getCollectedData(): ?CollectedData
    {
        return $this->collectedData;
    }

    public function setCollectedData(CollectedData $collectedData): static
    {
        $this->collectedData = $collectedData;

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

    public function getCountingCampaign(): ?CountingCampaign
    {
        return $this->countingCampaign;
    }

    public function setCountingCampaign(?CountingCampaign $countingCampaign): static
    {
        $this->countingCampaign = $countingCampaign;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

}
