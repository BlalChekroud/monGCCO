<?php

namespace App\Entity;

use App\Repository\NatureReserveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NatureReserveRepository::class)]
class NatureReserve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reserveName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'natureReserves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, SiteCollection>
     */
    #[ORM\OneToMany(targetEntity: SiteCollection::class, mappedBy: 'natureReserve')]
    private Collection $siteCollections;

    #[ORM\ManyToOne(inversedBy: 'natureReservesLeader')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reserveLeader = null;

    public function __construct()
    {
        $this->siteCollections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReserveName(): ?string
    {
        return $this->reserveName;
    }

    public function setReserveName(string $reserveName): static
    {
        $this->reserveName = $reserveName;

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
            $siteCollection->setNatureReserve($this);
        }

        return $this;
    }

    public function removeSiteCollection(SiteCollection $siteCollection): static
    {
        if ($this->siteCollections->removeElement($siteCollection)) {
            // set the owning side to null (unless already changed)
            if ($siteCollection->getNatureReserve() === $this) {
                $siteCollection->setNatureReserve(null);
            }
        }

        return $this;
    }

    public function getReserveLeader(): ?User
    {
        return $this->reserveLeader;
    }

    public function setReserveLeader(?User $reserveLeader): static
    {
        $this->reserveLeader = $reserveLeader;

        return $this;
    }
}
