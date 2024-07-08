<?php

namespace App\Entity;

use App\Repository\SiteCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteCollectionRepository::class)]
class SiteCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $siteName = null;

    #[ORM\Column(length: 25)]
    private ?string $siteCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nationalSiteCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $internationalSiteCode = null;

    #[ORM\Column(length: 255)]
    private ?string $latDepart = null;

    #[ORM\Column(length: 255)]
    private ?string $longDepart = null;

    #[ORM\Column(length: 255)]
    private ?string $latFin = null;

    #[ORM\Column(length: 255)]
    private ?string $longFin = null;

    #[ORM\Column(length: 255)]
    private ?string $region = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $parentSiteName = null;

    /**
     * @var Collection<int, CountingCampaign>
     */
    #[ORM\ManyToMany(targetEntity: CountingCampaign::class, mappedBy: 'siteCollection')]
    private Collection $countingCampaigns;

    #[ORM\Column(length: 50)]
    private ?string $country = null;

    /**
     * @var Collection<int, CollectedData>
     */
    // #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'siteCollection')]
    // private Collection $collectedData;

    public function __construct()
    {
        $this->countingCampaigns = new ArrayCollection();
        // $this->collectedData = new ArrayCollection();
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getParentSiteName(): ?string
    {
        return $this->parentSiteName;
    }

    public function setParentSiteName(?string $parentSiteName): static
    {
        $this->parentSiteName = $parentSiteName;

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

    // /**
    //  * @return Collection<int, CollectedData>
    //  */
    // public function getCollectedData(): Collection
    // {
    //     return $this->collectedData;
    // }

    // public function addCollectedData(CollectedData $collectedData): static
    // {
    //     if (!$this->collectedData->contains($collectedData)) {
    //         $this->collectedData->add($collectedData);
    //         $collectedData->setSiteCollection($this);
    //     }

    //     return $this;
    // }

    // public function removeCollectedData(CollectedData $collectedData): static
    // {
    //     if ($this->collectedData->removeElement($collectedData)) {
    //         // set the owning side to null (unless already changed)
    //         if ($collectedData->getSiteCollection() === $this) {
    //             $collectedData->setSiteCollection(null);
    //         }
    //     }

    //     return $this;
    // }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

}
