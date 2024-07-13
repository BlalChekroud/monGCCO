<?php

namespace App\Entity;

use App\Repository\CountingCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountingCampaignRepository::class)]
class CountingCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $campaignName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, SiteCollection>
     */
    #[ORM\ManyToMany(targetEntity: SiteCollection::class, inversedBy: 'countingCampaigns')]
    private Collection $siteCollection;

    /**
     * @var Collection<int, CollectedData>
     */
    #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'countingCampaign')]
    private Collection $collectedData;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'status')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CampaignStatus $campaignStatus = null;

    public function __construct()
    {
        $this->siteCollection = new ArrayCollection();
        $this->collectedData = new ArrayCollection();
    }

    public function generateCampaignName(): void
    {
        if ($this->getStartDate() === null || $this->getEndDate() === null) {
            throw new InvalidArgumentException('Les dates de début et de fin doivent être définies pour générer le nom de la campagne.');
        }

        $regionNames = [];
        foreach ($this->getSiteCollection() as $site) {
            $regionNames[] = $site->getRegion();
        }
        $uniqueRegionNames = array_unique($regionNames);

        $startDate = $this->getStartDate()->format('d-m-Y');
        $endDate = $this->getEndDate()->format('d-m-Y');

        $this->campaignName = sprintf('%s %s (%s - %s)','Campagne', implode(', ', $uniqueRegionNames), $startDate, $endDate);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaignName(): ?string
    {
        return $this->campaignName;
    }

    public function setCampaignName(string $campaignName): static
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    /**
     * @return Collection<int, SiteCollection>
     */
    public function getSiteCollection(): Collection
    {
        return $this->siteCollection;
    }

    public function addSiteCollection(SiteCollection $siteCollection): static
    {
        if (!$this->siteCollection->contains($siteCollection)) {
            $this->siteCollection->add($siteCollection);
        }

        return $this;
    }

    public function removeSiteCollection(SiteCollection $siteCollection): static
    {
        $this->siteCollection->removeElement($siteCollection);

        return $this;
    }

    /**
     * @return Collection<int, CollectedData>
     */
    public function getCollectedData(): Collection
    {
        return $this->collectedData;
    }

    public function addCollectedData(CollectedData $collectedData): static
    {
        if (!$this->collectedData->contains($collectedData)) {
            $this->collectedData->add($collectedData);
            $collectedData->setCountingCampaign($this);
        }

        return $this;
    }

    public function removeCollectedData(CollectedData $collectedData): static
    {
        if ($this->collectedData->removeElement($collectedData)) {
            // set the owning side to null (unless already changed)
            if ($collectedData->getCountingCampaign() === $this) {
                $collectedData->setCountingCampaign(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCampaignStatus(): ?CampaignStatus
    {
        return $this->campaignStatus;
    }

    public function setCampaignStatus(?CampaignStatus $campaignStatus): static
    {
        $this->campaignStatus = $campaignStatus;

        return $this;
    }

}
