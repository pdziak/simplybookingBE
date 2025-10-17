<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
// #[ApiResource] - Temporarily disabled due to getNativeType bug in API Platform 4.1.24+
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['product:read', 'product:write'])]
    private Category $category;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Groups(['product:read', 'product:write'])]
    private string $productName;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $productDescription = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $productImage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Product price is required')]
    #[Assert\Type(type: 'numeric', message: 'Product price must be a number')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Product price must be greater than or equal to 0')]
    #[Groups(['product:read', 'product:write'])]
    private float $productPrice;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Product stock is required')]
    #[Assert\Type(type: 'integer', message: 'Product stock must be a number')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Product stock must be greater than or equal to 0')]
    #[Groups(['product:read', 'product:write'])]
    private int $productStock;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $productSku = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['product:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(?string $productDescription): static
    {
        $this->productDescription = $productDescription;
        return $this;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function setProductImage(?string $productImage): static
    {
        $this->productImage = $productImage;
        return $this;
    }

    public function getProductPrice(): float
    {
        return $this->productPrice;
    }

    public function setProductPrice(float $productPrice): static
    {
        $this->productPrice = $productPrice;
        return $this;
    }

    public function getProductStock(): int
    {
        return $this->productStock;
    }

    public function setProductStock(int $productStock): static
    {
        $this->productStock = $productStock;
        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductSku(?string $productSku): static
    {
        $this->productSku = $productSku;
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
