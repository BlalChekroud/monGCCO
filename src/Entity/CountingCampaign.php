<?php

namespace App\Entity;

use App\Repository\CountingCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
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
    // #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $campaignName = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // /**
    //  * @var Collection<int, SiteCollection>
    //  */
    // #[ORM\ManyToMany(targetEntity: SiteCollection::class, inversedBy: 'countingCampaigns')]
    // private Collection $siteCollection;

    /**
     * @var Collection<int, CollectedData>
     */
    #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'countingCampaign')]
    private Collection $collectedData;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    // #[ORM\ManyToOne(inversedBy: 'status')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?CampaignStatus $campaignStatus = null;

    // /**
    //  * @var Collection<int, AgentsGroup>
    //  */
    // #[ORM\ManyToMany(targetEntity: AgentsGroup::class, inversedBy: 'agents')]
    // private Collection $agentsGroups;

    /**
     * @var Collection<int, EnvironmentalConditions>
     */
    #[ORM\OneToMany(targetEntity: EnvironmentalConditions::class, mappedBy: 'countingCampaign', orphanRemoval: true)]
    private Collection $environmentalConditions;

    #[ORM\ManyToOne(inversedBy: 'countingCampaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 10)]
    private ?string $campaignStatus = null;

    /**
     * @var Collection<int, SiteAgentsGroup>
     */
    #[ORM\OneToMany(targetEntity: SiteAgentsGroup::class, mappedBy: 'countingCampaign', orphanRemoval: true)]
    private Collection $siteAgentsGroups;

    public function __construct()
    {
        // $this->siteCollection = new ArrayCollection();
        $this->collectedData = new ArrayCollection();
        // $this->agentsGroups = new ArrayCollection();
        $this->environmentalConditions = new ArrayCollection();
        $this->siteAgentsGroups = new ArrayCollection();
    }

    public function generateCampaignName(): void
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($startDate === null || $endDate === null) {
            throw new \InvalidArgumentException('Les dates de début et de fin ne peuvent pas être les mêmes.');
        }
        
        if ($startDate->format('Y-m-d H:i:s') === $endDate->format('Y-m-d H:i:s')) {
            throw new \InvalidArgumentException('Les dates de début et de fin ne peuvent pas être les mêmes.');
        }

        if (!$this->getSiteAgentsGroups()) {
            throw new \InvalidArgumentException('Les sites et leurs groupes doivent être définies pour générer le nom de la campagne.');
        }

        $regionCode = [];
        foreach ($this->getSiteAgentsGroups() as $sag) {
            $regionCode[] = $sag->getSiteCollection()->getCity()->getRegion()->getRegionCode();
        }
        $uniqueregionCode = array_unique($regionCode);

        $startYear = $this->getStartDate()->format('Y');
        $campaignId = $this->getId();

        // $this->campaignName = sprintf('%s %s %s - %s', implode(',',$uniqueregionCode), implode(',', $uniqueCityNames) ,$startYear ,$campaignId);
        $this->campaignName = sprintf('%s %s - %s', implode(',',$uniqueregionCode) ,$startYear ,$campaignId);
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

    // /**
    //  * @return Collection<int, SiteCollection>
    //  */
    // public function getSiteCollection(): Collection
    // {
    //     return $this->siteCollection;
    // }

    // public function addSiteCollection(SiteCollection $siteCollection): static
    // {
    //     if (!$this->siteCollection->contains($siteCollection)) {
    //         $this->siteCollection->add($siteCollection);
    //     }

    //     return $this;
    // }

    // public function removeSiteCollection(SiteCollection $siteCollection): static
    // {
    //     $this->siteCollection->removeElement($siteCollection);

    //     return $this;
    // }

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

    // public function getCampaignStatus(): ?CampaignStatus
    // {
    //     return $this->campaignStatus;
    // }

    // public function setCampaignStatus(?CampaignStatus $campaignStatus): static
    // {
    //     $this->campaignStatus = $campaignStatus;

    //     return $this;
    // }


    // /**
    //  * @return Collection<int, AgentsGroup>
    //  */
    // public function getAgentsGroups(): Collection
    // {
    //     return $this->agentsGroups;
    // }

    // public function addAgentsGroup(AgentsGroup $agentsGroup): static
    // {
    //     if (!$this->agentsGroups->contains($agentsGroup)) {
    //         $this->agentsGroups->add($agentsGroup);
    //         $agentsGroup->addAgent($this);
    //     }

    //     return $this;
    // }

    // public function removeAgentsGroup(AgentsGroup $agentsGroup): static
    // {
    //     if ($this->agentsGroups->removeElement($agentsGroup)) {
    //         $agentsGroup->removeAgent($this);
    //     }

    //     return $this;
    // }

    /**
     * @return Collection<int, EnvironmentalConditions>
     */
    public function getEnvironmentalConditions(): Collection
    {
        return $this->environmentalConditions;
    }

    public function addEnvironmentalCondition(EnvironmentalConditions $environmentalCondition): static
    {
        if (!$this->environmentalConditions->contains($environmentalCondition)) {
            $this->environmentalConditions->add($environmentalCondition);
            $environmentalCondition->setCountingCampaign($this);
        }

        return $this;
    }

    public function removeEnvironmentalCondition(EnvironmentalConditions $environmentalCondition): static
    {
        if ($this->environmentalConditions->removeElement($environmentalCondition)) {
            // set the owning side to null (unless already changed)
            if ($environmentalCondition->getCountingCampaign() === $this) {
                $environmentalCondition->setCountingCampaign(null);
            }
        }

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

    public function getCampaignStatus(): ?string
    {
        return $this->campaignStatus;
    }

    public function setCampaignStatus(string $campaignStatus): static
    {
        $this->campaignStatus = $campaignStatus;

        return $this;
    }

    /**
     * @return Collection<int, SiteAgentsGroup>
     */
    public function getSiteAgentsGroups(): Collection
    {
        return $this->siteAgentsGroups;
    }

    public function addSiteAgentsGroup(SiteAgentsGroup $siteAgentsGroup): static
    {
        if (!$this->siteAgentsGroups->contains($siteAgentsGroup)) {
            $this->siteAgentsGroups->add($siteAgentsGroup);
            $siteAgentsGroup->setCountingCampaign($this);
        }

        return $this;
    }

    public function removeSiteAgentsGroup(SiteAgentsGroup $siteAgentsGroup): static
    {
        if ($this->siteAgentsGroups->removeElement($siteAgentsGroup)) {
            // set the owning side to null (unless already changed)
            if ($siteAgentsGroup->getCountingCampaign() === $this) {
                $siteAgentsGroup->setCountingCampaign(null);
            }
        }

        return $this;
    }

}
