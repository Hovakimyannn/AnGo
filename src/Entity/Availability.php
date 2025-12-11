<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository; // <--- Avelacvac e
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)] // <--- Kapvac e
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ArtistProfile $artist = null;

    // 1 = Erkushabti, 7 = Kiraki
    #[ORM\Column]
    private ?int $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column]
    private ?bool $isDayOff = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtist(): ?ArtistProfile
    {
        return $this->artist;
    }

    public function setArtist(?ArtistProfile $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isIsDayOff(): ?bool
    {
        return $this->isDayOff;
    }

    public function setIsDayOff(bool $isDayOff): static
    {
        $this->isDayOff = $isDayOff;

        return $this;
    }
}