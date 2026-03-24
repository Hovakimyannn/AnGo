<?php

namespace App\Entity;

use App\Repository\DidYouKnowPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DidYouKnowPostRepository::class)]
#[ORM\Table(name: 'did_you_know_post')]
#[ORM\Index(name: 'idx_did_you_know_post_published', columns: ['is_published', 'published_at'])]
class DidYouKnowPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    /** Filename in public/uploads/posts/ for grid/list (generated on upload). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageThumbnailUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $robotsDirective = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ogDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogImageAlt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, DidYouKnowComment>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: DidYouKnowComment::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    /**
     * @var Collection<int, DidYouKnowRating>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: DidYouKnowRating::class, orphanRemoval: true)]
    private Collection $ratings;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        if (!$this->slug || trim($this->slug) === '') {
            $this->slug = self::slugify($title);
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $slug = $slug !== null ? trim($slug) : null;
        $this->slug = $slug !== '' ? $slug : ($this->title ? self::slugify($this->title) : null);

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getImageThumbnailUrl(): ?string
    {
        return $this->imageThumbnailUrl;
    }

    public function setImageThumbnailUrl(?string $imageThumbnailUrl): static
    {
        $this->imageThumbnailUrl = $imageThumbnailUrl;

        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle !== null ? trim($seoTitle) : null;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription !== null ? trim($metaDescription) : null;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $canonicalUrl = $canonicalUrl !== null ? trim($canonicalUrl) : null;
        $this->canonicalUrl = $canonicalUrl !== '' ? $canonicalUrl : null;

        return $this;
    }

    public function getRobotsDirective(): ?string
    {
        return $this->robotsDirective;
    }

    public function setRobotsDirective(?string $robotsDirective): static
    {
        $robotsDirective = $robotsDirective !== null ? trim($robotsDirective) : null;
        $this->robotsDirective = $robotsDirective !== '' ? $robotsDirective : null;

        return $this;
    }

    public function getOgTitle(): ?string
    {
        return $this->ogTitle;
    }

    public function setOgTitle(?string $ogTitle): static
    {
        $this->ogTitle = $ogTitle !== null ? trim($ogTitle) : null;

        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgDescription(?string $ogDescription): static
    {
        $this->ogDescription = $ogDescription !== null ? trim($ogDescription) : null;

        return $this;
    }

    public function getOgImageUrl(): ?string
    {
        return $this->ogImageUrl;
    }

    public function setOgImageUrl(?string $ogImageUrl): static
    {
        $ogImageUrl = $ogImageUrl !== null ? trim($ogImageUrl) : null;
        $this->ogImageUrl = $ogImageUrl !== '' ? $ogImageUrl : null;

        return $this;
    }

    public function getOgImageAlt(): ?string
    {
        return $this->ogImageAlt;
    }

    public function setOgImageAlt(?string $ogImageAlt): static
    {
        $this->ogImageAlt = $ogImageAlt !== null ? trim($ogImageAlt) : null;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, DidYouKnowComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return Collection<int, DidYouKnowRating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function getExcerpt(int $maxLen = 200): string
    {
        $text = html_entity_decode((string) $this->content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text ?? '') ?? '';
        $text = trim($text);
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLen)) . '…';
    }

    public function __toString(): string
    {
        return (string) ($this->title ?? 'Post');
    }

    private static function slugify(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? $text;
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text) ?? $text;
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text) ?? $text;
        $text = strtolower($text);

        return $text !== '' ? $text : 'post';
    }
}
