<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity]
#[ORM\Table(name: 'budgets')]
// #[ApiResource] - Temporarily disabled due to getNativeType bug in API Platform 4.1.24+
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['budget:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'budgets')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['budget:read', 'budget:write'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: App::class, inversedBy: 'budgets')]
    #[ORM\JoinColumn(name: 'app_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['budget:read', 'budget:write'])]
    private App $app;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Budget amount is required')]
    #[Assert\Type(type: 'numeric', message: 'Budget amount must be a number')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Budget amount must be greater than or equal to 0')]
    #[Groups(['budget:write'])]
    private string $budget;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['budget:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['budget:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
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

    public function getBudget(): string
    {
        return $this->budget;
    }

    #[Groups(['budget:read'])]
    public function getBudgetAmount(): float
    {
        return (float) $this->budget;
    }

    public function setBudget(string $budget): static
    {
        $this->budget = $budget;
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
