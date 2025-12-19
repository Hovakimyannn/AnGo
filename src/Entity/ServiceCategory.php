<?php

namespace App\Entity;

use App\Repository\ServiceCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceCategoryRepository::class)]
#[ORM\Table(name: 'service_category')]
#[ORM\UniqueConstraint(name: 'uniq_service_category_key', columns: ['key_name'])]
class ServiceCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'key_name', length: 50)]
    private ?string $key = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => 100])]
    private int $sortOrder = 100;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function __toString(): string
    {
        $key = (string) ($this->key ?? '');
        $label = (string) ($this->label ?? $key);
        $label = trim($label) !== '' ? $label : $key;

        if ($key !== '' && $label !== '' && $label !== $key) {
            return "{$label} ({$key})";
        }

        return $label !== '' ? $label : $key;
    }
}


