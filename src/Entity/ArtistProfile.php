<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ArtistProfile
{
    private const FALLBACK_CATEGORY_LABELS = [
        'hair' => 'Վարսահարդարում',
        'makeup' => 'Դիմահարդարում',
        'nails' => 'Մատնահարդարում',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?ServiceCategory $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    // Avelacvac dasht nkarneri hamar (Sa er pakasum)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'artistProfiles')]
    private Collection $services;

    /**
     * @var Collection<int, ArtistPost>
     */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: ArtistPost::class, orphanRemoval: true)]
    #[ORM\OrderBy(['publishedAt' => 'DESC', 'createdAt' => 'DESC'])]
    private Collection $posts;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSpecialization(): string
    {
        $label = $this->category?->getLabel();
        if ($label !== null && trim($label) !== '') {
            return $label;
        }

        // Fallback for legacy data (before category was set): infer from services
        $keys = [];
        foreach ($this->services as $svc) {
            $k = $svc->getCategory();
            if ($k) {
                $keys[(string) $k] = true;
            }
        }
        if (count($keys) === 0) {
            return '';
        }

        $order = ['hair' => 1, 'makeup' => 2, 'nails' => 3];
        $best = array_key_first($keys);
        foreach (array_keys($keys) as $k) {
            if (($order[$k] ?? 99) < ($order[$best] ?? 99)) {
                $best = $k;
            }
        }

        return self::FALLBACK_CATEGORY_LABELS[$best] ?? $best;
    }

    public function getCategory(): ?ServiceCategory
    {
        return $this->category;
    }

    public function setCategory(?ServiceCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): static
    {
        $this->coverImageUrl = $coverImageUrl;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        $this->services->removeElement($service);

        return $this;
    }

    /**
     * @return Collection<int, ArtistPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    // EasyAdmin-i hamar: Vorpeszi drop-down-um cuyc ta anuny
    public function __toString(): string
    {
        return $this->user ? $this->user->getFirstName() . ' ' . $this->user->getLastName() : 'Artist';
    }
}