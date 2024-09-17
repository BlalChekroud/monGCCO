<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\CityRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Cette ville existe déjà.')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $latitude = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $longitude = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // #[ORM\ManyToOne(inversedBy: 'city')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?Country $country = null;

    /**
     * @var Collection<int, SiteCollection>
     */
    #[ORM\OneToMany(targetEntity: SiteCollection::class, mappedBy: 'city')]
    private Collection $siteCollections;

    #[ORM\ManyToOne(inversedBy: 'cities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Region $region = null;

    public function __construct()
    {
        $this->siteCollections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

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

    // public function getCountry(): ?Country
    // {
    //     return $this->country;
    // }

    // public function setCountry(?Country $country): static
    // {
    //     $this->country = $country;

    //     return $this;
    // }

    /**
     * @return Collection<int, SiteCollection>
     */
    public function getSiteCollections(): Collection
    {
        return $this->siteCollections;
    }

    public function addSiteCollection(SiteCollection $siteCollection): static
    {
        if (!$this->siteCollections->contains($siteCollection)) {
            $this->siteCollections->add($siteCollection);
            $siteCollection->setCity($this);
        }

        return $this;
    }

    public function removeSiteCollection(SiteCollection $siteCollection): static
    {
        if ($this->siteCollections->removeElement($siteCollection)) {
            // set the owning side to null (unless already changed)
            if ($siteCollection->getCity() === $this) {
                $siteCollection->setCity(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;

        return $this;
    }
}
