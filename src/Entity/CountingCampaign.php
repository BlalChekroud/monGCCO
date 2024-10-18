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

    /**
     * @var Collection<int, CollectedData>
     */
    #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'countingCampaign')]
    private Collection $collectedData;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, EnvironmentalConditions>
     */
    #[ORM\OneToMany(targetEntity: EnvironmentalConditions::class, mappedBy: 'countingCampaign', orphanRemoval: true)]
    private Collection $environmentalConditions;

    #[ORM\ManyToOne(inversedBy: 'countingCampaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    // #[ORM\Column(length: 10)]
    // private ?string $campaignStatus = null;

    /**
     * @var Collection<int, SiteAgentsGroup>
     */
    #[ORM\OneToMany(targetEntity: SiteAgentsGroup::class, mappedBy: 'countingCampaign', orphanRemoval: true)]
    private Collection $siteAgentsGroups;

    #[ORM\ManyToOne(inversedBy: 'campaignStatus')]
    private ?CampaignStatus $campaignStatus = null;

    public function __construct()
    {
        $this->collectedData = new ArrayCollection();
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

        if ($startDate >= $endDate) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
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

        $this->campaignName = sprintf('%s %s-%s', implode(',',$uniqueregionCode) ,$startYear ,$campaignId);
    }


    /**
     * Calcule le nombre total d'agents impliqués dans la campagne sans compter de doublons
     *
     * @return int
     */
    public function getTotalAgents(): int
    {
        // Utilisation d'un tableau pour suivre les agents déjà comptés
        $uniqueAgents = [];

        foreach ($this->getSiteAgentsGroups() as $siteAgentsGroup) {
            foreach ($siteAgentsGroup->getAgentsGroup() as $group) {
                foreach ($group->getGroupMember() as $agent) {
                    // Utilisation de l'ID de l'agent comme clé pour éviter les doublons
                    $agentId = $agent->getId();
                    if (!in_array($agentId, $uniqueAgents)) {
                        $uniqueAgents[] = $agentId;
                    }
                }
            }
        }

        // Le nombre total d'agents est la taille du tableau des agents uniques
        return count($uniqueAgents);
    }


    /**
     * Retourne le nombre total de collectes (CollectedData) associées à la campagne
     *
     * @return int
     */
    public function getTotalCollects(): int
    {
        $totalCollects = 0;
        foreach ($this->siteAgentsGroups as $group) {
            $site = $group->getSiteCollection();
            foreach ($site->getCollectedData() as $collect) {
                if ($collect->getCountingCampaign() === $this) {
                    $totalCollects++;
                }
            }
        }
        return $totalCollects;
    }


    /**
     * Récupère toutes les méthodes de collecte utilisées dans la campagne
     *
     * @return array
     */
    public function getMethodsUsed(): array
    {
        $methodsUsed = [];

        foreach ($this->getSiteAgentsGroups() as $siteAgentsGroup) {
            $site = $siteAgentsGroup->getSiteCollection();
            if ($site) {
                foreach ($site->getCollectedData() as $collect) {
                    foreach ($collect->getMethod() as $method) {
                        $methodName = $method->getLabel();
                        if (!in_array($methodName, $methodsUsed)) {
                            $methodsUsed[] = $methodName;
                        }
                    }
                }
            }
        }

        return $methodsUsed;
    }
    public function getAverageEnvironmentalConditions(): array
    {
        $conditions = [
            'most_common_weather' => '',
            'most_common_ice' => '',
            'most_common_tidal' => '',
            'most_common_water' => '',
            'disturbed_percentage' => 0
        ];

        // Exemple simplifié, vous devez ajuster selon vos modèles et structures de données.
        $weatherConditions = [];
        $totalDisturbed = 0;
        $totalCollects = 0;

        foreach ($this->siteAgentsGroups as $group) {
            $site = $group->getSiteCollection();
            foreach ($site->getCollectedData() as $collect) {
                if ($collect->getCountingCampaign() === $this) {
                    $totalCollects++;
                    $weather = $collect->getEnvironmentalConditions()->getWeather()->getLabel();
                    $ice = $collect->getEnvironmentalConditions()->getIce()->getLabel();
                    $tidal = $collect->getEnvironmentalConditions()->getTidal()->getLabel();
                    $water = $collect->getEnvironmentalConditions()->getWater()->getLabel();
                    $disturbed = $collect->getEnvironmentalConditions()->getDisturbed() ? 1 : 0;

                    // Comptage des conditions
                    $weatherConditions['weather'][$weather] = ($weatherConditions['weather'][$weather] ?? 0) + 1;
                    $weatherConditions['ice'][$ice] = ($weatherConditions['ice'][$ice] ?? 0) + 1;
                    $weatherConditions['tidal'][$tidal] = ($weatherConditions['tidal'][$tidal] ?? 0) + 1;
                    $weatherConditions['water'][$water] = ($weatherConditions['water'][$water] ?? 0) + 1;
                    $totalDisturbed += $disturbed;
                }
            }
        }

        if ($totalCollects > 0) {
            $conditions['most_common_weather'] = array_search(max($weatherConditions['weather']), $weatherConditions['weather']);
            $conditions['most_common_ice'] = array_search(max($weatherConditions['ice']), $weatherConditions['ice']);
            $conditions['most_common_tidal'] = array_search(max($weatherConditions['tidal']), $weatherConditions['tidal']);
            $conditions['most_common_water'] = array_search(max($weatherConditions['water']), $weatherConditions['water']);
            $conditions['disturbed_percentage'] = ($totalDisturbed / $totalCollects) * 100;
        }

        return $conditions;
    }

    private function getMostCommonCondition(array $conditions): ?string
    {
        if (empty($conditions)) {
            return null;
        }
        $counted = array_count_values($conditions);
        arsort($counted);
        return array_key_first($counted);
    }

    
    public function getWeatherConditionPercentages(): array
    {
        $weatherConditions = [];
        $totalConditions = 0;

        foreach ($this->getCollectedData() as $collect) {
            $environmentalConditions = $collect->getEnvironmentalConditions();
            if ($environmentalConditions) {
                $totalConditions++;
                $weatherLabel = $environmentalConditions->getWeather()->getLabel();
                if (!isset($weatherConditions[$weatherLabel])) {
                    $weatherConditions[$weatherLabel] = 0;
                }
                $weatherConditions[$weatherLabel]++;
            }
        }

        // Calcul du pourcentage pour chaque condition météo
        foreach ($weatherConditions as $label => $count) {
            $weatherConditions[$label] = ($count / $totalConditions) * 100;
        }

        return $weatherConditions;
    }

    /**
     * Retourne le nombre total pour chaque espèce dans la campagne
     *
     * @return array
     */
    public function getTotalCountBySpecies(): array
    {
        $speciesCounts = [];

        foreach ($this->getCollectedData() as $collectedData) {
            foreach ($collectedData->getBirdSpeciesCounts() as $birdSpeciesCount) {
                $speciesName = $birdSpeciesCount->getBirdSpecies()->getScientificName();
                $count = $birdSpeciesCount->getCount();

                if (!isset($speciesCounts[$speciesName])) {
                    $speciesCounts[$speciesName] = 0;
                }
                $speciesCounts[$speciesName] += $count;
            }
        }

        return $speciesCounts;
    }

    /**
     * Calcule le nombre total d'oiseaux comptés dans la campagne
     *
     * @return int
     */
    public function getTotalCountsCampaign(): int
    {
        $totalCountCampaign = 0;

        foreach ($this->getTotalCountBySpecies() as $birdSpeciesCountsTotal) {
            $totalCountCampaign += $birdSpeciesCountsTotal;
        }

        return $totalCountCampaign;
    }

    /**
     * Retourne le nombre total d'espèces uniques observées dans une campagne
     *
     * @return int
     */
    public function getTotalUniqueSpecies(): int
    {
        $uniqueSpecies = [];

        foreach ($this->getCollectedData() as $collectedData) {
            foreach ($collectedData->getBirdSpeciesCounts() as $birdSpeciesCount) {
                $speciesName = $birdSpeciesCount->getBirdSpecies()->getScientificName();
                $uniqueSpecies[$speciesName] = true; // Utiliser le nom scientifique comme clé
            }
        }

        return count($uniqueSpecies); // Retourner le nombre d'espèces uniques
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
