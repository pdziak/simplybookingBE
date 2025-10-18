<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/cart', name: 'api_cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    private function buildCartResponse(Cart $cart): array
    {
        $cartData = [
            'id' => $cart->getId(),
            'user' => [
                'id' => $cart->getUser()->getId(),
                'email' => $cart->getUser()->getEmail(),
            ],
            'items' => [],
            'totalItems' => $cart->getTotalItems(),
            'totalPrice' => $cart->getTotalPrice(),
            'createdAt' => $cart->getCreatedAt()->format('c'),
            'updatedAt' => $cart->getUpdatedAt()->format('c'),
        ];

        foreach ($cart->getItems() as $item) {
            $cartData['items'][] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $item->getProduct()->getId(),
                    'productName' => $item->getProduct()->getProductName(),
                    'productPrice' => $item->getProduct()->getProductPrice(),
                    'productDescription' => $item->getProduct()->getProductDescription(),
                    'productImage' => $item->getProduct()->getProductImage(),
                    'category' => [
                        'id' => $item->getProduct()->getCategory()->getId(),
                        'categoryName' => $item->getProduct()->getCategory()->getCategoryName(),
                    ],
                ],
                'quantity' => $item->getQuantity(),
                'createdAt' => $item->getCreatedAt()->format('c'),
                'updatedAt' => $item->getUpdatedAt()->format('c'),
            ];
        }

        return $cartData;
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function getCart(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->entityManager->getRepository(Cart::class)
            ->findOneBy(['user' => $user]);

        if (!$cart) {
            // Create empty cart if none exists
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $this->json($this->buildCartResponse($cart));
    }

    #[Route('/items', name: 'add_item', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productId']) || !isset($data['quantity'])) {
            return $this->json(['error' => 'productId and quantity are required'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->entityManager->getRepository(Product::class)
            ->find($data['productId']);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Get or create cart
        $cart = $this->entityManager->getRepository(Cart::class)
            ->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
        }

        // Check if item already exists in cart
        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Update quantity
            $existingItem->setQuantity($existingItem->getQuantity() + $data['quantity']);
            $existingItem->setUpdatedAt(new \DateTimeImmutable());
        } else {
            // Create new cart item
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($data['quantity']);
            $cart->addItem($cartItem);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->buildCartResponse($cart));
    }

    #[Route('/items/{productId}', name: 'update_item', methods: ['PUT'])]
    public function updateItem(int $productId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['quantity'])) {
            return $this->json(['error' => 'quantity is required'], Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->entityManager->getRepository(Cart::class)
            ->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        // Find the cart item
        $cartItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $productId) {
                $cartItem = $item;
                break;
            }
        }

        if (!$cartItem) {
            return $this->json(['error' => 'Item not found in cart'], Response::HTTP_NOT_FOUND);
        }

        if ($data['quantity'] <= 0) {
            // Remove item if quantity is 0 or negative
            $cart->removeItem($cartItem);
        } else {
            $cartItem->setQuantity($data['quantity']);
            $cartItem->setUpdatedAt(new \DateTimeImmutable());
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->buildCartResponse($cart));
    }

    #[Route('/items/{productId}', name: 'remove_item', methods: ['DELETE'])]
    public function removeItem(int $productId): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->entityManager->getRepository(Cart::class)
            ->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        // Find and remove the cart item
        $cartItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $productId) {
                $cartItem = $item;
                break;
            }
        }

        if (!$cartItem) {
            return $this->json(['error' => 'Item not found in cart'], Response::HTTP_NOT_FOUND);
        }

        $cart->removeItem($cartItem);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->buildCartResponse($cart));
    }

    #[Route('', name: 'clear', methods: ['DELETE'])]
    public function clearCart(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->entityManager->getRepository(Cart::class)
            ->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        // Clear all items
        foreach ($cart->getItems() as $item) {
            $cart->removeItem($item);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->buildCartResponse($cart));
    }
}
