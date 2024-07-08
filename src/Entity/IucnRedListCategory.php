<?php

namespace App\Entity;

use App\Repository\IucnRedListCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IucnRedListCategoryRepository::class)]
class IucnRedListCategory
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
    #[ORM\OneToMany(targetEntity: BirdSpecies::class, mappedBy: 'iucnRedListCategory')]
    private Collection $iucnRedListCategory;

    public function __construct()
    {
        $this->iucnRedListCategory = new ArrayCollection();
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
    public function getIucnRedListCategory(): Collection
    {
        return $this->iucnRedListCategory;
    }

    public function addIucnRedListCategory(BirdSpecies $iucnRedListCategory): static
    {
        if (!$this->iucnRedListCategory->contains($iucnRedListCategory)) {
            $this->iucnRedListCategory->add($iucnRedListCategory);
            $iucnRedListCategory->setIucnRedListCategory($this);
        }

        return $this;
    }

    public function removeIucnRedListCategory(BirdSpecies $iucnRedListCategory): static
    {
        if ($this->iucnRedListCategory->removeElement($iucnRedListCategory)) {
            // set the owning side to null (unless already changed)
            if ($iucnRedListCategory->getIucnRedListCategory() === $this) {
                $iucnRedListCategory->setIucnRedListCategory(null);
            }
        }

        return $this;
    }
}