<?php

namespace App\Entity;

use App\Repository\SiteCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteCollectionRepository::class)]
class SiteCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $siteName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $siteCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationalSiteCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $internationalSiteCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $latDepart = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $longDepart = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $latFin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $longFin = null;

    /**
     * @var Collection<int, CountingCampaign>
     */
    #[ORM\ManyToMany(targetEntity: CountingCampaign::class, mappedBy: 'siteCollection')]
    private Collection $countingCampaigns;

    #[ORM\ManyToOne(inversedBy: 'siteCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    /**
     * @var Collection<int, EnvironmentalConditions>
     */
    #[ORM\OneToMany(targetEntity: EnvironmentalConditions::class, mappedBy: 'siteCollection', orphanRemoval: true)]
    private Collection $environmentalConditions;

    /**
     * @var Collection<int, CollectedData>
     */
    #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'siteCollection')]
    private Collection $collectedData;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'siteCollections')]
    private ?self $parentSite = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentSite')]
    private Collection $siteCollections;

    public function __construct()
    {
        $this->countingCampaigns = new ArrayCollection();
        $this->collectedData = new ArrayCollection();
        $this->environmentalConditions = new ArrayCollection();
        $this->siteCollections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getSiteCode(): ?string
    {
        return $this->siteCode;
    }

    public function setSiteCode(?string $siteCode): static
    {
        $this->siteCode = $siteCode;

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

    public function getNationalSiteCode(): ?string
    {
        return $this->nationalSiteCode;
    }

    public function setNationalSiteCode(?string $nationalSiteCode): static
    {
        $this->nationalSiteCode = $nationalSiteCode;

        return $this;
    }

    public function getInternationalSiteCode(): ?string
    {
        return $this->internationalSiteCode;
    }

    public function setInternationalSiteCode(?string $internationalSiteCode): static
    {
        $this->internationalSiteCode = $internationalSiteCode;

        return $this;
    }

    public function getLatDepart(): ?string
    {
        return $this->latDepart;
    }

    public function setLatDepart(?string $latDepart): static
    {
        $this->latDepart = $latDepart;

        return $this;
    }

    public function getLongDepart(): ?string
    {
        return $this->longDepart;
    }

    public function setLongDepart(?string $longDepart): static
    {
        $this->longDepart = $longDepart;

        return $this;
    }

    public function getLatFin(): ?string
    {
        return $this->latFin;
    }

    public function setLatFin(string $latFin): static
    {
        $this->latFin = $latFin;

        return $this;
    }

    public function getLongFin(): ?string
    {
        return $this->longFin;
    }

    public function setLongFin(string $longFin): static
    {
        $this->longFin = $longFin;

        return $this;
    }

    /**
     * @return Collection<int, CountingCampaign>
     */
    public function getCountingCampaigns(): Collection
    {
        return $this->countingCampaigns;
    }

    public function addCountingCampaign(CountingCampaign $countingCampaign): static
    {
        if (!$this->countingCampaigns->contains($countingCampaign)) {
            $this->countingCampaigns->add($countingCampaign);
            $countingCampaign->addSiteCollection($this);
        }

        return $this;
    }

    public function removeCountingCampaign(CountingCampaign $countingCampaign): static
    {
        if ($this->countingCampaigns->removeElement($countingCampaign)) {
            $countingCampaign->removeSiteCollection($this);
        }

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
            $collectedData->setSiteCollection($this);
        }

        return $this;
    }

    public function removeCollectedData(CollectedData $collectedData): static
    {
        if ($this->collectedData->removeElement($collectedData)) {
            // set the owning side to null (unless already changed)
            if ($collectedData->getSiteCollection() === $this) {
                $collectedData->setSiteCollection(null);
            }
        }

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

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
            $environmentalCondition->setSiteCollection($this);
        }

        return $this;
    }

    public function removeEnvironmentalCondition(EnvironmentalConditions $environmentalCondition): static
    {
        if ($this->environmentalConditions->removeElement($environmentalCondition)) {
            // set the owning side to null (unless already changed)
            if ($environmentalCondition->getSiteCollection() === $this) {
                $environmentalCondition->setSiteCollection(null);
            }
        }

        return $this;
    }

    public function getParentSite(): ?self
    {
        return $this->parentSite;
    }

    public function setParentSite(?self $parentSite): static
    {
        $this->parentSite = $parentSite;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSiteCollections(): Collection
    {
        return $this->siteCollections;
    }

    public function addSiteCollection(self $siteCollection): static
    {
        if (!$this->siteCollections->contains($siteCollection)) {
            $this->siteCollections->add($siteCollection);
            $siteCollection->setParentSite($this);
        }

        return $this;
    }

    public function removeSiteCollection(self $siteCollection): static
    {
        if ($this->siteCollections->removeElement($siteCollection)) {
            // set the owning side to null (unless already changed)
            if ($siteCollection->getParentSite() === $this) {
                $siteCollection->setParentSite(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getSiteName() ?: '';
    }
}
