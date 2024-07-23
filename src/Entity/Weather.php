<?php

namespace App\Entity;

use App\Repository\WeatherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeatherRepository::class)]
class Weather
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, EnvironmentalConditions>
     */
    #[ORM\OneToMany(targetEntity: EnvironmentalConditions::class, mappedBy: 'weather')]
    private Collection $environmentalConditions;

    public function __construct()
    {
        $this->environmentalConditions = new ArrayCollection();
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
            $environmentalCondition->setWeather($this);
        }

        return $this;
    }

    public function removeEnvironmentalCondition(EnvironmentalConditions $environmentalCondition): static
    {
        if ($this->environmentalConditions->removeElement($environmentalCondition)) {
            // set the owning side to null (unless already changed)
            if ($environmentalCondition->getWeather() === $this) {
                $environmentalCondition->setWeather(null);
            }
        }

        return $this;
    }
}
