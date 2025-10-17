<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'carts')]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['cart:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['cart:read'])]
    private User $user;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['cart:read'])]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['cart:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['cart:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    /**
     * @return Collection<int, CartItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
        }

        return $this;
    }

    public function removeItem(CartItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCart() === $this) {
                $item->setCart(null);
            }
        }

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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getTotalItems(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }

    public function getTotalPrice(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getProduct()->getProductPrice() * $item->getQuantity();
        }
        return $total;
    }
}
