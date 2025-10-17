<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
// #[ApiResource] - Temporarily disabled due to getNativeType bug in API Platform 4.1.24+
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['category:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Groups(['category:read', 'category:write', 'product:read'])]
    private string $categoryName;

    #[ORM\ManyToOne(targetEntity: App::class)]
    #[ORM\JoinColumn(name: 'app_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['category:read'])]
    private App $app;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['category:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['category:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): static
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function setApp(App $app): static
    {
        $this->app = $app;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
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
}
