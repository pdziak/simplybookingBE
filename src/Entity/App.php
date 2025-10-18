<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity]
#[ORM\Table(name: 'apps')]
// #[ApiResource] - Temporarily disabled due to getNativeType bug in API Platform 4.1.24+
class App
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read', 'app:subdomain'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Slug is required')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Slug must contain only lowercase letters, numbers, and hyphens'
    )]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Company name is required')]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private string $companyName;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email must be valid')]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private string $email;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank(message: 'Logo is required')]
    #[Assert\Regex(
        pattern: '/^logos\/[a-zA-Z0-9\-_\.]+$/',
        message: 'Logo must be a valid logo path'
    )]
    #[Groups(['app:read', 'app:write', 'app:subdomain'])]
    private string $logo;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['app:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['app:read', 'app:subdomain'])]
    private User $owner;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'app', cascade: ['persist', 'remove'])]
    #[Groups(['app:read', 'app:subdomain'])]
    private $categories;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'assignedApps')]
    #[Groups(['app:read'])]
    private $assignedUsers;

    #[ORM\OneToMany(targetEntity: Budget::class, mappedBy: 'app', cascade: ['persist', 'remove'])]
    #[Groups(['app:read'])]
    private $budgets;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->assignedUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->budgets = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): static
    {
        $this->logo = $logo;
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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Category>
     */
    public function getCategories(): \Doctrine\Common\Collections\Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setApp($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getApp() === $this) {
                $category->setApp(null);
            }
        }

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getAssignedUsers(): \Doctrine\Common\Collections\Collection
    {
        return $this->assignedUsers;
    }

    public function addAssignedUser(User $user): static
    {
        if (!$this->assignedUsers->contains($user)) {
            $this->assignedUsers->add($user);
            $user->addAssignedApp($this);
        }

        return $this;
    }

    public function removeAssignedUser(User $user): static
    {
        if ($this->assignedUsers->removeElement($user)) {
            $user->removeAssignedApp($this);
        }

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Budget>
     */
    public function getBudgets(): \Doctrine\Common\Collections\Collection
    {
        return $this->budgets;
    }

    public function addBudget(Budget $budget): static
    {
        if (!$this->budgets->contains($budget)) {
            $this->budgets->add($budget);
            $budget->setApp($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): static
    {
        if ($this->budgets->removeElement($budget)) {
            // set the owning side to null (unless already changed)
            if ($budget->getApp() === $this) {
                $budget->setApp(null);
            }
        }

        return $this;
    }
}
