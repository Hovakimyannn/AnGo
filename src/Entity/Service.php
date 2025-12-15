<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Category: 'hair', 'nails', 'makeup'
    #[ORM\Column(length: 50)]
    private ?string $category = null;

    // Tevoxutyuny ropeov (orinak` 60)
    #[ORM\Column]
    private ?int $durationMinutes = null;

    #[ORM\Column]
    private ?float $price = null;

    /**
     * @var Collection<int, ArtistProfile>
     */
    #[ORM\ManyToMany(targetEntity: ArtistProfile::class, mappedBy: 'services')]
    private Collection $artistProfiles;

    public function __construct()
    {
        $this->artistProfiles = new ArrayCollection();
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->price . ' AMD)';
    }

    /**
     * @return Collection<int, ArtistProfile>
     */
    public function getArtistProfiles(): Collection
    {
        return $this->artistProfiles;
    }
}
