<?php

namespace App\Entity;

use App\Repository\SiteAgentsGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteAgentsGroupRepository::class)]
class SiteAgentsGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, AgentsGroup>
     */
    #[ORM\ManyToMany(targetEntity: AgentsGroup::class, inversedBy: 'siteAgentsGroups')]
    private Collection $agentsGroup;

    #[ORM\ManyToOne(inversedBy: 'siteAgentsGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CountingCampaign $countingCampaign = null;

    #[ORM\ManyToOne(inversedBy: 'siteAgentsGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SiteCollection $siteCollection = null;

    public function __construct()
    {
        $this->agentsGroup = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection<int, AgentsGroup>
     */
    public function getAgentsGroup(): Collection
    {
        return $this->agentsGroup;
    }

    public function addAgentsGroup(AgentsGroup $agentsGroup): static
    {
        if (!$this->agentsGroup->contains($agentsGroup)) {
            $this->agentsGroup->add($agentsGroup);
        }

        return $this;
    }

    public function removeAgentsGroup(AgentsGroup $agentsGroup): static
    {
        $this->agentsGroup->removeElement($agentsGroup);

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

    public function getSiteCollection(): ?SiteCollection
    {
        return $this->siteCollection;
    }

    public function setSiteCollection(?SiteCollection $siteCollection): static
    {
        $this->siteCollection = $siteCollection;

        return $this;
    }

}
