<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\App;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\BudgetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/orders', name: 'orders_')]
class OrdersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private BudgetService $budgetService
    ) {}

    #[Route('', name: 'create', methods: ['POST', 'OPTIONS'])]
    public function createOrder(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return new JsonResponse();
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Wymagane uwierzytelnienie'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Nieprawidłowe dane JSON'], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            $requiredFields = ['firstname', 'lastname', 'email', 'deliveryType', 'appId', 'cartItems'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return $this->json(['error' => "Pole '{$field}' jest wymagane"], Response::HTTP_BAD_REQUEST);
                }
            }

            // Get the app
            $app = $this->entityManager->getRepository(App::class)->find($data['appId']);
            if (!$app) {
                return $this->json(['error' => 'Aplikacja nie znaleziona'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this app
            $hasAccess = false;
            if ($app->getOwner()->getId() === $user->getId()) {
                $hasAccess = true;
            } else {
                $hasAccess = $app->getAssignedUsers()->contains($user);
            }

            if (!$hasAccess) {
                return $this->json(['error' => 'Odmowa dostępu do tej aplikacji'], Response::HTTP_FORBIDDEN);
            }

            // Process cart items and calculate total
            $cartTotal = 0;
            $cartItems = $data['cartItems'] ?? [];
            
            if (empty($cartItems)) {
                return $this->json(['error' => 'Koszyk jest pusty'], Response::HTTP_BAD_REQUEST);
            }

            // Validate and calculate total for each cart item
            foreach ($cartItems as $cartItem) {
                if (!isset($cartItem['productId']) || !isset($cartItem['quantity'])) {
                    return $this->json(['error' => 'Nieprawidłowe dane elementu koszyka'], Response::HTTP_BAD_REQUEST);
                }

                $product = $this->entityManager->getRepository(Product::class)->find($cartItem['productId']);
                if (!$product) {
                    return $this->json(['error' => 'Produkt nie znaleziony: ' . $cartItem['productId']], Response::HTTP_NOT_FOUND);
                }

                $quantity = (int) $cartItem['quantity'];
                $price = (float) $product->getProductPrice();
                $cartTotal += $price * $quantity;
            }

            // Check if user has sufficient budget
            if (!$this->budgetService->hasSufficientBudget($user, $app, $cartTotal)) {
                $currentBudget = $this->budgetService->getBudgetAmount($user, $app);
                return $this->json([
                    'error' => 'Niewystarczający budżet',
                    'message' => 'Niewystarczający budżet. Potrzebujesz ' . number_format($cartTotal, 2) . ' zł, a masz ' . number_format($currentBudget, 2) . ' zł',
                    'required' => $cartTotal,
                    'available' => $currentBudget,
                    'shortfall' => $cartTotal - $currentBudget
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create new order
            $order = new Order();
            $order->setFirstname($data['firstname']);
            $order->setLastname($data['lastname']);
            $order->setEmail($data['email']);
            $order->setShippingLocation($data['deliveryType']);
            $order->setShippingAddress($data['shipping'] ?? null);
            $order->setUser($user);
            $order->setApp($app);

            // Create order products
            foreach ($cartItems as $cartItem) {
                $product = $this->entityManager->getRepository(Product::class)->find($cartItem['productId']);
                if (!$product) {
                    return $this->json(['error' => 'Produkt nie znaleziony'], Response::HTTP_BAD_REQUEST);
                }
                
                $quantity = (int) $cartItem['quantity'];
                $unitPrice = (float) $product->getProductPrice();
                $totalPrice = $unitPrice * $quantity;

                error_log("Creating OrderProduct: Product ID: {$product->getId()}, Name: {$product->getProductName()}, Quantity: {$quantity}, UnitPrice: {$unitPrice}, TotalPrice: {$totalPrice}");

                $orderProduct = new OrderProduct();
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($quantity);
                $orderProduct->setUnitPrice($unitPrice);
                $orderProduct->setTotalPrice($totalPrice);
                $orderProduct->setOrder($order);

                $order->addOrderProduct($orderProduct);
                
                // Explicitly persist the OrderProduct
                $this->entityManager->persist($orderProduct);
            }
            
            error_log("Order created with " . $order->getOrderProducts()->count() . " products");

            // Validate the order
            $errors = $this->validator->validate($order);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['error' => 'Walidacja nie powiodła się', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            // Save to database
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Reduce user's budget by cart total
            try {
                $this->budgetService->reduceBudget($user, $app, $cartTotal);
            } catch (\Exception $e) {
                // If budget reduction fails, rollback the order
                $this->entityManager->remove($order);
                $this->entityManager->flush();
                
                error_log('Redukcja budżetu nie powiodła się, order rolled back: ' . $e->getMessage());
                return $this->json([
                    'error' => 'Redukcja budżetu nie powiodła się',
                    'message' => 'Nie udało się zaktualizować budżetu. Zamówienie zostało anulowane.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Return the created order
            $jsonData = $this->serializer->serialize($order, 'json', [
                'groups' => ['order:read'],
                'circular_reference_handler' => function ($object, $format, $context) {
                    if ($object instanceof \App\Entity\Order) {
                        return [
                            'id' => $object->getId(),
                            'firstname' => $object->getFirstname(),
                            'lastname' => $object->getLastname(),
                            'email' => $object->getEmail(),
                            'shippingLocation' => $object->getShippingLocation(),
                            'shippingAddress' => $object->getShippingAddress(),
                            'createdAt' => $object->getCreatedAt()->format('Y-m-d H:i:s'),
                            'modifiedAt' => $object->getModifiedAt()?->format('Y-m-d H:i:s'),
                            'fullName' => $object->getFullName(),
                            'deliveryTypeDisplay' => $object->getDeliveryTypeDisplay(),
                            'orderProducts' => $object->getOrderProducts()->map(function($orderProduct) {
                                return [
                                    'id' => $orderProduct->getId(),
                                    'product' => [
                                        'id' => $orderProduct->getProduct()->getId(),
                                        'name' => $orderProduct->getProduct()->getProductName(),
                                        'price' => $orderProduct->getProduct()->getProductPrice()
                                    ],
                                    'quantity' => $orderProduct->getQuantity(),
                                    'unitPrice' => (float) $orderProduct->getUnitPrice(),
                                    'totalPrice' => (float) $orderProduct->getTotalPrice()
                                ];
                            })->toArray()
                        ];
                    } elseif ($object instanceof \App\Entity\User) {
                        return [
                            'id' => $object->getId(),
                            'email' => $object->getEmail(),
                            'firstName' => $object->getFirstName(),
                            'lastName' => $object->getLastName()
                        ];
                    } elseif ($object instanceof \App\Entity\App) {
                        return [
                            'id' => $object->getId(),
                            'title' => $object->getTitle(),
                            'slug' => $object->getSlug()
                        ];
                    }
                    
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            ]);

            return new JsonResponse($jsonData, Response::HTTP_CREATED, [], true);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Nie udało się utworzyć zamówienia',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/{userId}', name: 'get_user_orders', methods: ['GET', 'OPTIONS'])]
    public function getUserOrders(int $userId, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return new JsonResponse();
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Wymagane uwierzytelnienie'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is requesting their own orders or has admin access
        if ($user->getId() !== $userId && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Odmowa dostępu'], Response::HTTP_FORBIDDEN);
        }

        try {
            $orders = $this->entityManager->getRepository(Order::class)
                ->findBy(['user' => $userId], ['createdAt' => 'DESC']);

            $jsonData = $this->serializer->serialize($orders, 'json', [
                'groups' => ['order:read'],
                'circular_reference_handler' => function ($object, $format, $context) {
                    if ($object instanceof \App\Entity\Order) {
                        return [
                            'id' => $object->getId(),
                            'firstname' => $object->getFirstname(),
                            'lastname' => $object->getLastname(),
                            'email' => $object->getEmail(),
                            'shippingLocation' => $object->getShippingLocation(),
                            'shippingAddress' => $object->getShippingAddress(),
                            'createdAt' => $object->getCreatedAt()->format('Y-m-d H:i:s'),
                            'modifiedAt' => $object->getModifiedAt()?->format('Y-m-d H:i:s'),
                            'fullName' => $object->getFullName(),
                            'deliveryTypeDisplay' => $object->getDeliveryTypeDisplay(),
                            'orderProducts' => $object->getOrderProducts()->map(function($orderProduct) {
                                return [
                                    'id' => $orderProduct->getId(),
                                    'product' => [
                                        'id' => $orderProduct->getProduct()->getId(),
                                        'name' => $orderProduct->getProduct()->getProductName(),
                                        'price' => $orderProduct->getProduct()->getProductPrice()
                                    ],
                                    'quantity' => $orderProduct->getQuantity(),
                                    'unitPrice' => (float) $orderProduct->getUnitPrice(),
                                    'totalPrice' => (float) $orderProduct->getTotalPrice()
                                ];
                            })->toArray()
                        ];
                    } elseif ($object instanceof \App\Entity\User) {
                        return [
                            'id' => $object->getId(),
                            'email' => $object->getEmail(),
                            'firstName' => $object->getFirstName(),
                            'lastName' => $object->getLastName()
                        ];
                    } elseif ($object instanceof \App\Entity\App) {
                        return [
                            'id' => $object->getId(),
                            'title' => $object->getTitle(),
                            'slug' => $object->getSlug()
                        ];
                    }
                    
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            ]);

            return new JsonResponse($jsonData, Response::HTTP_OK, [], true);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Nie udało się pobrać zamówień',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/app/{appId}', name: 'get_app_orders', methods: ['GET', 'OPTIONS'])]
    public function getAppOrders(int $appId, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return new JsonResponse();
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Wymagane uwierzytelnienie'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Get the app
            $app = $this->entityManager->getRepository(App::class)->find($appId);
            if (!$app) {
                return $this->json(['error' => 'Aplikacja nie znaleziona'], Response::HTTP_NOT_FOUND);
            }

            // Check if user has access to this app
            $hasAccess = false;
            if ($app->getOwner()->getId() === $user->getId()) {
                $hasAccess = true;
            } else {
                $hasAccess = $app->getAssignedUsers()->contains($user);
            }

            if (!$hasAccess) {
                return $this->json(['error' => 'Odmowa dostępu do tej aplikacji'], Response::HTTP_FORBIDDEN);
            }

            // Get all orders for this app with their products
            $orders = $this->entityManager->getRepository(Order::class)
                ->createQueryBuilder('o')
                ->leftJoin('o.orderProducts', 'op')
                ->leftJoin('op.product', 'p')
                ->leftJoin('o.user', 'u')
                ->leftJoin('o.app', 'a')
                ->addSelect('op', 'p', 'u', 'a')
                ->where('o.app = :appId')
                ->setParameter('appId', $appId)
                ->orderBy('o.createdAt', 'DESC')
                ->getQuery()
                ->getResult();

            // Debug: Log the orders data
            error_log('Orders found: ' . count($orders));
            foreach ($orders as $order) {
                error_log('Order ' . $order->getId() . ' has ' . $order->getOrderProducts()->count() . ' products');
                foreach ($order->getOrderProducts() as $op) {
                    error_log('OrderProduct: ' . $op->getId() . ', Product: ' . ($op->getProduct() ? $op->getProduct()->getId() : 'NULL'));
                }
            }

            $jsonData = $this->serializer->serialize($orders, 'json', [
                'groups' => ['order:read'],
                'circular_reference_handler' => function ($object, $format, $context) {
                    if ($object instanceof \App\Entity\Order) {
                        return [
                            'id' => $object->getId(),
                            'firstname' => $object->getFirstname(),
                            'lastname' => $object->getLastname(),
                            'email' => $object->getEmail(),
                            'shippingLocation' => $object->getShippingLocation(),
                            'shippingAddress' => $object->getShippingAddress(),
                            'createdAt' => $object->getCreatedAt()->format('Y-m-d H:i:s'),
                            'modifiedAt' => $object->getModifiedAt()?->format('Y-m-d H:i:s'),
                            'fullName' => $object->getFullName(),
                            'deliveryTypeDisplay' => $object->getDeliveryTypeDisplay(),
                            'orderProducts' => $object->getOrderProducts()->map(function($orderProduct) {
                                return [
                                    'id' => $orderProduct->getId(),
                                    'product' => [
                                        'id' => $orderProduct->getProduct()->getId(),
                                        'name' => $orderProduct->getProduct()->getProductName(),
                                        'price' => $orderProduct->getProduct()->getProductPrice()
                                    ],
                                    'quantity' => $orderProduct->getQuantity(),
                                    'unitPrice' => (float) $orderProduct->getUnitPrice(),
                                    'totalPrice' => (float) $orderProduct->getTotalPrice()
                                ];
                            })->toArray(),
                            'user' => [
                                'id' => $object->getUser()->getId(),
                                'email' => $object->getUser()->getEmail(),
                                'firstName' => $object->getUser()->getFirstName(),
                                'lastName' => $object->getUser()->getLastName()
                            ],
                            'app' => [
                                'id' => $object->getApp()->getId(),
                                'title' => $object->getApp()->getTitle(),
                                'slug' => $object->getApp()->getSlug()
                            ]
                        ];
                    } elseif ($object instanceof \App\Entity\User) {
                        return [
                            'id' => $object->getId(),
                            'email' => $object->getEmail(),
                            'firstName' => $object->getFirstName(),
                            'lastName' => $object->getLastName()
                        ];
                    } elseif ($object instanceof \App\Entity\App) {
                        return [
                            'id' => $object->getId(),
                            'title' => $object->getTitle(),
                            'slug' => $object->getSlug()
                        ];
                    }
                    
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            ]);

            return new JsonResponse($jsonData, Response::HTTP_OK, [], true);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Nie udało się pobrać zamówień aplikacji',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'get_order', methods: ['GET', 'OPTIONS'])]
    public function getOrder(int $id, Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return new JsonResponse();
        }

        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Wymagane uwierzytelnienie'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $order = $this->entityManager->getRepository(Order::class)->find($id);
            
            if (!$order) {
                return $this->json(['error' => 'Zamówienie nie znalezione'], Response::HTTP_NOT_FOUND);
            }

            // Check if user owns this order or has admin access
            if ($order->getUser()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->json(['error' => 'Odmowa dostępu'], Response::HTTP_FORBIDDEN);
            }

            $jsonData = $this->serializer->serialize($order, 'json', [
                'groups' => ['order:read'],
                'circular_reference_handler' => function ($object, $format, $context) {
                    if ($object instanceof \App\Entity\Order) {
                        return [
                            'id' => $object->getId(),
                            'firstname' => $object->getFirstname(),
                            'lastname' => $object->getLastname(),
                            'email' => $object->getEmail(),
                            'shippingLocation' => $object->getShippingLocation(),
                            'shippingAddress' => $object->getShippingAddress(),
                            'createdAt' => $object->getCreatedAt()->format('Y-m-d H:i:s'),
                            'modifiedAt' => $object->getModifiedAt()?->format('Y-m-d H:i:s'),
                            'fullName' => $object->getFullName(),
                            'deliveryTypeDisplay' => $object->getDeliveryTypeDisplay(),
                            'orderProducts' => $object->getOrderProducts()->map(function($orderProduct) {
                                return [
                                    'id' => $orderProduct->getId(),
                                    'product' => [
                                        'id' => $orderProduct->getProduct()->getId(),
                                        'name' => $orderProduct->getProduct()->getProductName(),
                                        'price' => $orderProduct->getProduct()->getProductPrice()
                                    ],
                                    'quantity' => $orderProduct->getQuantity(),
                                    'unitPrice' => (float) $orderProduct->getUnitPrice(),
                                    'totalPrice' => (float) $orderProduct->getTotalPrice()
                                ];
                            })->toArray()
                        ];
                    } elseif ($object instanceof \App\Entity\User) {
                        return [
                            'id' => $object->getId(),
                            'email' => $object->getEmail(),
                            'firstName' => $object->getFirstName(),
                            'lastName' => $object->getLastName()
                        ];
                    } elseif ($object instanceof \App\Entity\App) {
                        return [
                            'id' => $object->getId(),
                            'title' => $object->getTitle(),
                            'slug' => $object->getSlug()
                        ];
                    }
                    
                    return method_exists($object, 'getId') ? $object->getId() : null;
                }
            ]);

            return new JsonResponse($jsonData, Response::HTTP_OK, [], true);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Nie udało się pobrać zamówienia',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
