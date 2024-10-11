<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Cet e-mail n\'est pas disponible, essayer avec un autre')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Veuillez entrer une adresse e-mail')]
    #[Assert\Email(message: 'L\'adresse e-mail "{{ value }}" n\'est pas une adresse e-mail valide.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'adresse e-mail ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, AgentsGroup>
     */
    #[ORM\ManyToMany(targetEntity: AgentsGroup::class, mappedBy: 'groupMember')]
    private Collection $agentsGroups;

    /**
     * @var Collection<int, AgentsGroup>
     */
    #[ORM\OneToMany(targetEntity: AgentsGroup::class, mappedBy: 'leader')]
    private Collection $leader;

    /**
     * @var Collection<int, EnvironmentalConditions>
     */
    #[ORM\OneToMany(targetEntity: EnvironmentalConditions::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $environmentalConditions;

    /**
     * @var Collection<int, CollectedData>
     */
    #[ORM\OneToMany(targetEntity: CollectedData::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $collectedData;

    /**
     * @var Collection<int, CountingCampaign>
     */
    #[ORM\OneToMany(targetEntity: CountingCampaign::class, mappedBy: 'createdBy')]
    private Collection $countingCampaigns;

    /**
     * @var Collection<int, AgentsGroup>
     */
    #[ORM\OneToMany(targetEntity: AgentsGroup::class, mappedBy: 'createdBy')]
    private Collection $groupCreatedBy;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Image $image = null;

    public function __construct()
    {
        $this->agentsGroups = new ArrayCollection();
        $this->leader = new ArrayCollection();
        $this->environmentalConditions = new ArrayCollection();
        $this->collectedData = new ArrayCollection();
        $this->countingCampaigns = new ArrayCollection();
        $this->groupCreatedBy = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        // // Ajouter le rôle TEAMLEADER si l'utilisateur est leader d'au moins un groupe
        // if (!$this->leader->isEmpty()) {
        //     $roles[] = 'ROLE_TEAMLEADER';
        // }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

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
     * @return Collection<int, AgentsGroup>
     */
    public function getAgentsGroups(): Collection
    {
        return $this->agentsGroups;
    }

    public function addAgentsGroup(AgentsGroup $agentsGroup): static
    {
        if (!$this->agentsGroups->contains($agentsGroup)) {
            $this->agentsGroups->add($agentsGroup);
            $agentsGroup->addGroupMember($this);
        }

        return $this;
    }

    public function removeAgentsGroup(AgentsGroup $agentsGroup): static
    {
        if ($this->agentsGroups->removeElement($agentsGroup)) {
            $agentsGroup->removeGroupMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, AgentsGroup>
     */
    public function getLeader(): Collection
    {
        return $this->leader;
    }

    public function addLeader(AgentsGroup $leader): static
    {
        if (!$this->leader->contains($leader)) {
            $this->leader->add($leader);
            $leader->setLeader($this);
        }

        return $this;
    }

    public function removeLeader(AgentsGroup $leader): static
    {
        if ($this->leader->removeElement($leader)) {
            // set the owning side to null (unless already changed)
            if ($leader->getLeader() === $this) {
                $leader->setLeader(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getEmail() ?: '';
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
            $environmentalCondition->setUser($this);
        }

        return $this;
    }

    public function removeEnvironmentalCondition(EnvironmentalConditions $environmentalCondition): static
    {
        if ($this->environmentalConditions->removeElement($environmentalCondition)) {
            // set the owning side to null (unless already changed)
            if ($environmentalCondition->getUser() === $this) {
                $environmentalCondition->setUser(null);
            }
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
            $collectedData->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCollectedData(CollectedData $collectedData): static
    {
        if ($this->collectedData->removeElement($collectedData)) {
            // set the owning side to null (unless already changed)
            if ($collectedData->getCreatedBy() === $this) {
                $collectedData->setCreatedBy(null);
            }
        }

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
            $countingCampaign->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCountingCampaign(CountingCampaign $countingCampaign): static
    {
        if ($this->countingCampaigns->removeElement($countingCampaign)) {
            // set the owning side to null (unless already changed)
            if ($countingCampaign->getCreatedBy() === $this) {
                $countingCampaign->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AgentsGroup>
     */
    public function getGroupCreatedBy(): Collection
    {
        return $this->groupCreatedBy;
    }

    public function addGroupCreatedBy(AgentsGroup $groupCreatedBy): static
    {
        if (!$this->groupCreatedBy->contains($groupCreatedBy)) {
            $this->groupCreatedBy->add($groupCreatedBy);
            $groupCreatedBy->setCreatedBy($this);
        }

        return $this;
    }

    public function removeGroupCreatedBy(AgentsGroup $groupCreatedBy): static
    {
        if ($this->groupCreatedBy->removeElement($groupCreatedBy)) {
            // set the owning side to null (unless already changed)
            if ($groupCreatedBy->getCreatedBy() === $this) {
                $groupCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): static
    {
        $this->image = $image;

        return $this;
    }

}
