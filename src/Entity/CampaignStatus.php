<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\CampaignStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignStatusRepository::class)]
class CampaignStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private ?string $label = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, CountingCampaign>
     */
    #[ORM\OneToMany(targetEntity: CountingCampaign::class, mappedBy: 'campaignStatus')]
    private Collection $campaignStatus;

    public function __construct()
    {
        $this->campaignStatus = new ArrayCollection();
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
    public function getCampaignStatus(): Collection
    {
        return $this->campaignStatus;
    }

    public function addCampaignStatus(CountingCampaign $campaignStatus): static
    {
        if (!$this->campaignStatus->contains($campaignStatus)) {
            $this->campaignStatus->add($campaignStatus);
            $campaignStatus->setCampaignStatus($this);
        }

        return $this;
    }

    public function removeCampaignStatus(CountingCampaign $campaignStatus): static
    {
        if ($this->campaignStatus->removeElement($campaignStatus)) {
            // set the owning side to null (unless already changed)
            if ($campaignStatus->getCampaignStatus() === $this) {
                $campaignStatus->setCampaignStatus(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel() ?: '';
    }
}
