<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['order:read', 'order:write'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'First name must be at least 2 characters', maxMessage: 'First name must be less than 255 characters')]
    #[Groups(['order:read', 'order:write'])]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Last name must be at least 2 characters', maxMessage: 'Last name must be less than 255 characters')]
    #[Groups(['order:read', 'order:write'])]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    #[Assert\Length(max: 255, maxMessage: 'Email must be less than 255 characters')]
    #[Groups(['order:read', 'order:write'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Delivery type is required')]
    #[Assert\Choice(choices: ['home', 'office'], message: 'Delivery type must be either home or office')]
    #[Groups(['order:read', 'order:write'])]
    private string $shippingLocation;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Shipping address must be less than 1000 characters')]
    #[Groups(['order:read', 'order:write'])]
    private ?string $shippingAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['order:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: App::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read'])]
    private ?App $app = null;

    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    #[Groups(['order:read'])]
    private Collection $orderProducts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orderProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getShippingLocation(): ?string
    {
        return $this->shippingLocation;
    }

    public function setShippingLocation(string $shippingLocation): static
    {
        $this->shippingLocation = $shippingLocation;
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        $this->modifiedAt = new \DateTimeImmutable();

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

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): static
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getApp(): ?App
    {
        return $this->app;
    }

    public function setApp(?App $app): static
    {
        $this->app = $app;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getDeliveryTypeDisplay(): string
    {
        return $this->shippingLocation === 'home' ? 'Do domu' : 'Do biura';
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getOrder() === $this) {
                $orderProduct->setOrder(null);
            }
        }

        return $this;
    }
}
