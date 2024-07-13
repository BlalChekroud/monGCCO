<?php

namespace App\Entity;

use App\Repository\CampaignStatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignStatusRepository::class)]
class CampaignStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $label = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, CountingCampaign>
     */
    #[ORM\OneToMany(targetEntity: CountingCampaign::class, mappedBy: 'campaignStatus')]
    private Collection $status;

    public function __construct()
    {
        $this->status = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    /**
     * @return Collection<int, CountingCampaign>
     */
    public function getStatus(): Collection
    {
        return $this->status;
    }

    public function addStatus(CountingCampaign $status): static
    {
        if (!$this->status->contains($status)) {
            $this->status->add($status);
            $status->setCampaignStatus($this);
        }

        return $this;
    }

    public function removeStatus(CountingCampaign $status): static
    {
        if ($this->status->removeElement($status)) {
            // set the owning side to null (unless already changed)
            if ($status->getCampaignStatus() === $this) {
                $status->setCampaignStatus(null);
            }
        }

        return $this;
    }
}
