<?php

namespace App\Command;

use App\Entity\App;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-demo-data',
    description: 'Create demo data for the demo subdomain',
)]
class CreateDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find or create demo app
        $demoApp = $this->entityManager->getRepository(App::class)
            ->findOneBy(['slug' => 'demo']);

        if (!$demoApp) {
            $io->error('Demo app not found! Please create it first.');
            return Command::FAILURE;
        }

        $io->note('Found demo app: ' . $demoApp->getTitle());

        // Create categories
        $categories = [
            ['name' => 'Electronics', 'products' => [
                ['name' => 'Wireless Headphones', 'description' => 'High-quality wireless headphones with noise cancellation', 'price' => 199.99, 'stock' => 50, 'sku' => 'WH-001'],
                ['name' => 'Smartphone', 'description' => 'Latest generation smartphone with 5G', 'price' => 799.99, 'stock' => 30, 'sku' => 'SP-001'],
                ['name' => 'Gaming Laptop', 'description' => 'High-performance gaming laptop with RTX graphics', 'price' => 1299.99, 'stock' => 15, 'sku' => 'GL-001'],
            ]],
            ['name' => 'Books', 'products' => [
                ['name' => 'Programming Guide', 'description' => 'Complete guide to modern programming', 'price' => 49.99, 'stock' => 100, 'sku' => 'PG-001'],
                ['name' => 'Fiction Novel', 'description' => 'Bestselling fiction novel', 'price' => 19.99, 'stock' => 75, 'sku' => 'FN-001'],
            ]],
            ['name' => 'Clothing', 'products' => [
                ['name' => 'T-Shirt', 'description' => 'Comfortable cotton t-shirt', 'price' => 24.99, 'stock' => 200, 'sku' => 'TS-001'],
                ['name' => 'Jeans', 'description' => 'Classic blue jeans', 'price' => 79.99, 'stock' => 150, 'sku' => 'JN-001'],
            ]],
        ];

        $totalProducts = 0;
        foreach ($categories as $categoryData) {
            // Create category
            $category = new Category();
            $category->setCategoryName($categoryData['name']);
            $category->setApp($demoApp);
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $io->text('Created category: ' . $categoryData['name']);

            // Create products for this category
            foreach ($categoryData['products'] as $productData) {
                $product = new Product();
                $product->setProductName($productData['name']);
                $product->setProductDescription($productData['description']);
                $product->setProductPrice($productData['price']);
                $product->setProductStock($productData['stock']);
                $product->setProductSku($productData['sku']);
                $product->setCategory($category);
                $this->entityManager->persist($product);
                $totalProducts++;
            }
        }

        $this->entityManager->flush();

        $io->success("Created demo data successfully!");
        $io->text("Categories: " . count($categories));
        $io->text("Products: " . $totalProducts);

        return Command::SUCCESS;
    }
}
