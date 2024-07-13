<?php

namespace App\Entity;

use App\Repository\CoverageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoverageRepository::class)]
class Coverage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, BirdSpecies>
     */
    #[ORM\OneToMany(targetEntity: BirdSpecies::class, mappedBy: 'coverage')]
    private Collection $coverage;

    public function __construct()
    {
        $this->coverage = new ArrayCollection();
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

    /**
     * @return Collection<int, BirdSpecies>
     */
    public function getCoverage(): Collection
    {
        return $this->coverage;
    }

    public function addCoverage(BirdSpecies $coverage): static
    {
        if (!$this->coverage->contains($coverage)) {
            $this->coverage->add($coverage);
            $coverage->setCoverage($this);
        }

        return $this;
    }

    public function removeCoverage(BirdSpecies $coverage): static
    {
        if ($this->coverage->removeElement($coverage)) {
            // set the owning side to null (unless already changed)
            if ($coverage->getCoverage() === $this) {
                $coverage->setCoverage(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel() ?: '';
    }
}
