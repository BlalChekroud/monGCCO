<?php

namespace App\Entity;

use App\Repository\AgentsGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: AgentsGroupRepository::class)]
class AgentsGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $groupName = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $leader = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'agentsGroups')]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    private Collection $groupMember;

    #[ORM\ManyToOne(inversedBy: 'agentsGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    /**
     * @var Collection<int, SiteAgentsGroup>
     */
    #[ORM\ManyToMany(targetEntity: SiteAgentsGroup::class, mappedBy: 'agentsGroup')]
    private Collection $siteAgentsGroups;

    #[ORM\ManyToOne(inversedBy: 'groupCreatedBy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->groupMember = new ArrayCollection();
        $this->agents = new ArrayCollection();
        $this->siteAgentsGroups = new ArrayCollection();
    }

    
    public function generateAgentsGroup(): void
    {
        if ($this->getGroupMember() === null) {
            throw new \InvalidArgumentException('Les membres du groupe doivent être définies pour générer le nom de groupe.');
        }
    
        $iso2Names = $this->getCountry()->getIso2();
        $startYear = $this->getCreatedAt()->format('Y');
        $groupId = $this->getId();
    
        if ($groupId === null) {
            throw new \RuntimeException('L\'ID du groupe doit être généré avant de pouvoir créer le nom du groupe.');
        }
        $this->groupName = sprintf('%s-%s %s %s', 'Groupe', $groupId, $iso2Names, $startYear);
    }
    
    public function validateLeader(): bool
    {
        return $this->getGroupMember()->contains($this->leader);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): static
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getLeader(): ?user
    {
        return $this->leader;
    }

    public function setLeader(?User $leader): static
    {
        $this->leader = $leader;

        return $this;
    }
    // public function setLeader(?User $leader): self
    // {
    //     if ($this->leader !== $leader) {
    //         // Retirer l'ancien leader
    //         if ($this->leader) {
    //             $this->leader->removeLeader($this);
    //         }

    //         // Définir le nouveau leader
    //         $this->leader = $leader;

    //         // Ajouter ce groupe au nouveau leader
    //         if ($leader) {
    //             $leader->addLeader($this);
    //         }
    //     }

    //     return $this;
    // }

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
     * @return Collection<int, User>
     */
    public function getGroupMember(): Collection
    {
        return $this->groupMember;
    }

    public function addGroupMember(User $groupMember): static
    {
        if (!$this->groupMember->contains($groupMember)) {
            $this->groupMember->add($groupMember);
        }

        return $this;
    }

    public function removeGroupMember(User $groupMember): static
    {
        $this->groupMember->removeElement($groupMember);

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

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
            $siteAgentsGroup->addAgentsGroup($this);
        }

        return $this;
    }

    public function removeSiteAgentsGroup(SiteAgentsGroup $siteAgentsGroup): static
    {
        if ($this->siteAgentsGroups->removeElement($siteAgentsGroup)) {
            $siteAgentsGroup->removeAgentsGroup($this);
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
}
