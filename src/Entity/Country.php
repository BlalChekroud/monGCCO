<?php

namespace App\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce pays existe déjà.')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 2)]
    private ?string $iso2 = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, City>
     */
    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'country')]
    private Collection $city;

    /**
     * @var Collection<int, AgentsGroup>
     */
    #[ORM\OneToMany(targetEntity: AgentsGroup::class, mappedBy: 'country')]
    private Collection $agentsGroups;
    // , orphanRemoval: false

    public function __construct()
    {
        $this->city = new ArrayCollection();
        $this->agentsGroups = new ArrayCollection();
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

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(string $iso2): static
    {
        $this->iso2 = $iso2;

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
     * @return Collection<int, City>
     */
    public function getCity(): Collection
    {
        return $this->city;
    }

    public function addCity(City $city): static
    {
        if (!$this->city->contains($city)) {
            $this->city->add($city);
            $city->setCountry($this);
        }

        return $this;
    }

    public function removeCity(City $city): static
    {
        if ($this->city->removeElement($city)) {
            // set the owning side to null (unless already changed)
            if ($city->getCountry() === $this) {
                $city->setCountry(null);
            }
        }

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
            $agentsGroup->setCountry($this);
        }

        return $this;
    }

    public function removeAgentsGroup(AgentsGroup $agentsGroup): static
    {
        if ($this->agentsGroups->removeElement($agentsGroup)) {
            // set the owning side to null (unless already changed)
            if ($agentsGroup->getCountry() === $this) {
                $agentsGroup->setCountry(null);
            }
        }

        return $this;
    }
}
