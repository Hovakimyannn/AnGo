<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ArtistProfile
{
    private const FALLBACK_CATEGORY_LABELS = [
        'hair' => 'Վարսահարդարում',
        'makeup' => 'Դիմահարդարում',
        'nails' => 'Մատնահարդարում',
        'pedicure' => 'Ոտնահարդարում',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Only persist: deleting an artist profile must NOT remove the linked User account.
    #[ORM\OneToOne(cascade: ['persist'])]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

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

    public function setUser(User $user): self
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

        $order = ['hair' => 1, 'makeup' => 2, 'nails' => 3, 'pedicure' => 4];
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

    public function setCategory(?ServiceCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): self
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): self
    {
        $this->coverImageUrl = $coverImageUrl;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeSlug(): void
    {
        if ($this->slug === null || (trim($this->slug) === '')) {
            $slugger = new AsciiSlugger();
            $base = $this->user ? $this->user->getFirstName() . ' ' . $this->user->getLastName() : 'artist';
            $this->slug = (string) $slugger->slug($base)->lower();
        }
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