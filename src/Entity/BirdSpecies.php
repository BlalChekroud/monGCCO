<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\BirdSpeciesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BirdSpeciesRepository::class)]
// #[Vich\Uploadable]
class BirdSpecies
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Vich\Uploadable()]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $scientificName = null;

    #[ORM\Column(length: 50)]
    private ?string $frenchName = null;

    #[ORM\Column(length: 25)]
    private ?string $wispeciescode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;
    
    #[Vich\UploadableField(mapping: "bird_Specy", fileNameProperty: "image")]
    #[Assert\Image()]
    private ?File $imageFile = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $authority = null;

    #[ORM\ManyToOne(inversedBy: 'birdSpecies')]
    #[ORM\JoinColumn(nullable: true)]
    private ?BirdFamily $birdFamily = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 2)]
    private ?string $birdLifeTaxTreat = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $commonName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $commonNameAlt = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $iucnRedListCategory = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $synonyms = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $taxonomicSources = null;

    #[ORM\Column(nullable: true)]
    private ?int $sisRecId = null;

    #[ORM\Column(nullable: true)]
    private ?int $spcRecId = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $subsppId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScientificName(): ?string
    {
        return $this->scientificName;
    }

    public function setScientificName(string $scientificName): static
    {
        $this->scientificName = $scientificName;

        return $this;
    }

    public function getFrenchName(): ?string
    {
        return $this->frenchName;
    }

    public function setFrenchName(string $frenchName): static
    {
        $this->frenchName = $frenchName;

        return $this;
    }

    public function getWispeciescode(): ?string
    {
        return $this->wispeciescode;
    }

    public function setWispeciescode(string $wispeciescode): static
    {
        $this->wispeciescode = $wispeciescode;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    public function setAuthority(?string $authority): static
    {
        $this->authority = $authority;

        return $this;
    }

    public function getBirdFamily(): ?BirdFamily
    {
        return $this->birdFamily;
    }

    public function setBirdFamily(?BirdFamily $birdFamily): static
    {
        $this->birdFamily = $birdFamily;

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

    public function getBirdLifeTaxTreat(): ?string
    {
        return $this->birdLifeTaxTreat;
    }

    public function setBirdLifeTaxTreat(string $birdLifeTaxTreat): static
    {
        $this->birdLifeTaxTreat = $birdLifeTaxTreat;

        return $this;
    }

    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    public function setCommonName(?string $commonName): static
    {
        $this->commonName = $commonName;

        return $this;
    }

    public function getCommonNameAlt(): ?string
    {
        return $this->commonNameAlt;
    }

    public function setCommonNameAlt(?string $commonNameAlt): static
    {
        $this->commonNameAlt = $commonNameAlt;

        return $this;
    }

    public function getIucnRedListCategory(): ?string
    {
        return $this->iucnRedListCategory;
    }

    public function setIucnRedListCategory(?string $iucnRedListCategory): static
    {
        $this->iucnRedListCategory = $iucnRedListCategory;

        return $this;
    }

    public function getSynonyms(): ?string
    {
        return $this->synonyms;
    }

    public function setSynonyms(?string $synonyms): static
    {
        $this->synonyms = $synonyms;

        return $this;
    }

    public function getTaxonomicSources(): ?string
    {
        return $this->taxonomicSources;
    }

    public function setTaxonomicSources(string $taxonomicSources): static
    {
        $this->taxonomicSources = $taxonomicSources;

        return $this;
    }

    public function getSisRecId(): ?int
    {
        return $this->sisRecId;
    }

    public function setSisRecId(?int $sisRecId): static
    {
        $this->sisRecId = $sisRecId;

        return $this;
    }

    public function getSpcRecId(): ?int
    {
        return $this->spcRecId;
    }

    public function setSpcRecId(?int $spcRecId): static
    {
        $this->spcRecId = $spcRecId;

        return $this;
    }

    public function getSubsppId(): ?string
    {
        return $this->subsppId;
    }

    public function setSubsppId(?string $subsppId): static
    {
        $this->subsppId = $subsppId;

        return $this;
    }
}
