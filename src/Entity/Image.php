<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Vich\Uploadable]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Column(length: 255)]
    // private ?string $imageFilename = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $imageFilename = null;

    #[Vich\UploadableField(mapping: 'images', fileNameProperty: 'imageFilename')]
    private ?File $imageFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'image', cascade: ['persist', 'remove'])]
    private ?User $userImage = null;

    // #[ORM\OneToOne(mappedBy: 'image', cascade: ['persist', 'remove'])]
    // private ?Logo $logo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFilename(?string $imageFilename): void
    {
        $this->imageFilename = $imageFilename;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    // public function getImageFilename(): ?string
    // {
    //     return $this->imageFilename;
    // }

    // public function setImageFilename(string $imageFilename): static
    // {
    //     $this->imageFilename = $imageFilename;

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

    public function getUserImage(): ?User
    {
        return $this->userImage;
    }

    public function setUserImage(?User $userImage): static
    {
        $this->userImage = $userImage;

        return $this;
    }

    // public function getLogo(): ?Logo
    // {
    //     return $this->logo;
    // }

    // public function setLogo(?Logo $logo): static
    // {
    //     // unset the owning side of the relation if necessary
    //     if ($logo === null && $this->logo !== null) {
    //         $this->logo->setImage(null);
    //     }

    //     // set the owning side of the relation if necessary
    //     if ($logo !== null && $logo->getImage() !== $this) {
    //         $logo->setImage($this);
    //     }

    //     $this->logo = $logo;

    //     return $this;
    // }
}
