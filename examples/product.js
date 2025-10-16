/**
 * Product API Usage Examples
 * 
 * This file demonstrates how to use the Product API endpoint (/api/products)
 * with JWT authentication.
 */

const API_BASE_URL = 'http://localhost/api';
const AUTH_ENDPOINT = `${API_BASE_URL}/auth`;

class ProductAPI {
    constructor() {
        this.token = null;
    }

    /**
     * Authenticate user and get JWT token
     */
    async authenticate(email, password) {
        try {
            const response = await fetch(`${AUTH_ENDPOINT}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password })
            });

            if (!response.ok) {
                throw new Error(`Authentication failed: ${response.status}`);
            }

            const data = await response.json();
            this.token = data.token;
            console.log('✅ Authentication successful');
            return this.token;
        } catch (error) {
            console.error('❌ Authentication failed:', error.message);
            throw error;
        }
    }

    /**
     * Register a new user
     */
    async register(email, password, firstName, lastName) {
        try {
            const response = await fetch(`${AUTH_ENDPOINT}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    email, 
                    password, 
                    firstName, 
                    lastName 
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Registration failed: ${error.error || response.status}`);
            }

            console.log('✅ User registered successfully');
            return await response.json();
        } catch (error) {
            console.error('❌ Registration failed:', error.message);
            throw error;
        }
    }

    /**
     * Get authentication headers
     */
    getAuthHeaders() {
        if (!this.token) {
            throw new Error('Not authenticated. Please login first.');
        }
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.token}`
        };
    }

    /**
     * Create a new product
     */
    async createProduct(productData) {
        try {
            const response = await fetch(`${API_BASE_URL}/products`, {
                method: 'POST',
                headers: this.getAuthHeaders(),
                body: JSON.stringify(productData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to create product: ${error.error || response.status}`);
            }

            const product = await response.json();
            console.log('✅ Product created successfully:', product);
            return product;
        } catch (error) {
            console.error('❌ Failed to create product:', error.message);
            throw error;
        }
    }

    /**
     * Get all products
     */
    async getProducts() {
        try {
            const response = await fetch(`${API_BASE_URL}/products`, {
                method: 'GET',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch products: ${response.status}`);
            }

            const products = await response.json();
            console.log('✅ Products fetched successfully:', products);
            return products;
        } catch (error) {
            console.error('❌ Failed to fetch products:', error.message);
            throw error;
        }
    }

    /**
     * Get a specific product by ID
     */
    async getProduct(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/products/${id}`, {
                method: 'GET',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch product: ${response.status}`);
            }

            const product = await response.json();
            console.log('✅ Product fetched successfully:', product);
            return product;
        } catch (error) {
            console.error('❌ Failed to fetch product:', error.message);
            throw error;
        }
    }

    /**
     * Update a product
     */
    async updateProduct(id, productData) {
        try {
            const response = await fetch(`${API_BASE_URL}/products/${id}`, {
                method: 'PUT',
                headers: this.getAuthHeaders(),
                body: JSON.stringify(productData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to update product: ${error.error || response.status}`);
            }

            const product = await response.json();
            console.log('✅ Product updated successfully:', product);
            return product;
        } catch (error) {
            console.error('❌ Failed to update product:', error.message);
            throw error;
        }
    }

    /**
     * Delete a product
     */
    async deleteProduct(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/products/${id}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to delete product: ${response.status}`);
            }

            const result = await response.json();
            console.log('✅ Product deleted successfully:', result.message);
            return true;
        } catch (error) {
            console.error('❌ Failed to delete product:', error.message);
            throw error;
        }
    }

    /**
     * Create a category (helper method for product creation)
     */
    async createCategory(categoryData) {
        try {
            const response = await fetch(`${API_BASE_URL}/categories`, {
                method: 'POST',
                headers: this.getAuthHeaders(),
                body: JSON.stringify(categoryData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to create category: ${error.error || response.status}`);
            }

            const category = await response.json();
            console.log('✅ Category created successfully:', category);
            return category;
        } catch (error) {
            console.error('❌ Failed to create category:', error.message);
            throw error;
        }
    }

    /**
     * Create an app (helper method for category creation)
     */
    async createApp(appData) {
        try {
            const response = await fetch(`${API_BASE_URL}/apps`, {
                method: 'POST',
                headers: this.getAuthHeaders(),
                body: JSON.stringify(appData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to create app: ${error.error || response.status}`);
            }

            const app = await response.json();
            console.log('✅ App created successfully:', app);
            return app;
        } catch (error) {
            console.error('❌ Failed to create app:', error.message);
            throw error;
        }
    }
}

// Example usage
async function demonstrateProductAPI() {
    const api = new ProductAPI();

    try {
        // Step 1: Register a new user (optional - skip if user already exists)
        console.log('📝 Registering new user...');
        try {
            await api.register(
                'demo@example.com',
                'password123',
                'Demo',
                'User'
            );
        } catch (error) {
            console.log('ℹ️ User might already exist, continuing...');
        }

        // Step 2: Authenticate
        console.log('🔐 Authenticating...');
        await api.authenticate('demo@example.com', 'password123');

        // Step 3: Create an app (required for categories)
        console.log('📱 Creating app...');
        const app = await api.createApp({
            title: 'E-commerce Store',
            slug: 'ecommerce-store',
            companyName: 'Demo Store Inc.',
            email: 'contact@demostore.com',
            description: 'A demo e-commerce store for testing products',
            logo: 'https://example.com/store-logo.png'
        });

        // Step 4: Create a category (required for products)
        console.log('📂 Creating category...');
        const category = await api.createCategory({
            categoryName: 'Electronics',
            app_id: app.id
        });

        // Step 5: Create a product with all fields
        console.log('🛍️ Creating product with all fields...');
        const product1 = await api.createProduct({
            productName: 'Wireless Headphones',
            productDescription: 'High-quality wireless headphones with noise cancellation',
            productImage: 'https://example.com/headphones.jpg',
            productPrice: 199.99,
            productStock: 50,
            productSku: 'WH-001',
            category_id: category.id
        });

        // Step 6: Create a minimal product (only required fields)
        console.log('🛍️ Creating minimal product...');
        const product2 = await api.createProduct({
            productName: 'USB Cable',
            productPrice: 9.99,
            productStock: 100,
            category_id: category.id
        });

        // Step 7: Get all products
        console.log('📋 Fetching all products...');
        const products = await api.getProducts();
        console.log(`Found ${products.length} products`);

        // Step 8: Get a specific product
        console.log('🔍 Fetching specific product...');
        const specificProduct = await api.getProduct(product1.id);

        // Step 9: Update a product
        console.log('✏️ Updating product...');
        const updatedProduct = await api.updateProduct(product1.id, {
            productName: 'Premium Wireless Headphones',
            productDescription: 'Updated description: Premium wireless headphones with advanced noise cancellation',
            productPrice: 249.99,
            productStock: 25,
            productSku: 'PWH-001'
        });

        // Step 10: Create another category and product
        console.log('📂 Creating another category...');
        const category2 = await api.createCategory({
            categoryName: 'Clothing',
            app_id: app.id
        });

        console.log('🛍️ Creating product in new category...');
        const product3 = await api.createProduct({
            productName: 'Cotton T-Shirt',
            productDescription: 'Comfortable 100% cotton t-shirt',
            productImage: 'https://example.com/tshirt.jpg',
            productPrice: 24.99,
            productStock: 75,
            productSku: 'TS-001',
            category_id: category2.id
        });

        // Step 11: Get all products again
        console.log('📋 Fetching all products after adding more...');
        const allProducts = await api.getProducts();
        console.log(`Now we have ${allProducts.length} products`);

        // Step 12: Delete a product
        console.log('🗑️ Deleting product...');
        await api.deleteProduct(product2.id);

        // Step 13: Get products again to confirm deletion
        console.log('📋 Fetching products after deletion...');
        const finalProducts = await api.getProducts();
        console.log(`Now we have ${finalProducts.length} products`);

        console.log('🎉 All product operations completed successfully!');

    } catch (error) {
        console.error('💥 Demo failed:', error.message);
    }
}

// Example of error handling for validation
async function demonstrateProductValidationErrors() {
    const api = new ProductAPI();

    try {
        await api.authenticate('demo@example.com', 'password123');

        console.log('🚫 Testing product validation errors...');

        // Test 1: Missing required fields
        try {
            await api.createProduct({
                productName: 'Incomplete Product'
                // Missing productPrice, productStock and category_id
            });
        } catch (error) {
            console.log('✅ Caught missing required fields error:', error.message);
        }

        // Test 2: Invalid price (negative)
        try {
            await api.createProduct({
                productName: 'Invalid Price Product',
                productPrice: -10.99,
                productStock: 10,
                category_id: 1
            });
        } catch (error) {
            console.log('✅ Caught invalid price error:', error.message);
        }

        // Test 3: Invalid price (non-numeric)
        try {
            await api.createProduct({
                productName: 'Invalid Price Product',
                productPrice: 'not-a-number',
                productStock: 10,
                category_id: 1
            });
        } catch (error) {
            console.log('✅ Caught invalid price type error:', error.message);
        }

        // Test 4: Invalid stock (negative)
        try {
            await api.createProduct({
                productName: 'Invalid Stock Product',
                productPrice: 29.99,
                productStock: -5,
                category_id: 1
            });
        } catch (error) {
            console.log('✅ Caught invalid stock error:', error.message);
        }

        // Test 5: Invalid stock (non-numeric)
        try {
            await api.createProduct({
                productName: 'Invalid Stock Product',
                productPrice: 29.99,
                productStock: 'not-a-number',
                category_id: 1
            });
        } catch (error) {
            console.log('✅ Caught invalid stock type error:', error.message);
        }

        // Test 6: Non-existent category
        try {
            await api.createProduct({
                productName: 'Product with Invalid Category',
                productPrice: 29.99,
                productStock: 10,
                category_id: 99999
            });
        } catch (error) {
            console.log('✅ Caught invalid category error:', error.message);
        }

        // Test 7: Access denied (trying to access other user's data)
        try {
            await api.getProduct(99999);
        } catch (error) {
            console.log('✅ Caught access denied error:', error.message);
        }

    } catch (error) {
        console.error('💥 Validation demo failed:', error.message);
    }
}

// Example of working with different product types
async function demonstrateProductTypes() {
    const api = new ProductAPI();

    try {
        await api.authenticate('demo@example.com', 'password123');

        console.log('🛍️ Demonstrating different product types...');

        // Create app and categories
        const app = await api.createApp({
            title: 'Multi-Category Store',
            slug: 'multi-category-store',
            companyName: 'Multi Store Inc.',
            email: 'contact@multistore.com',
            description: 'A store with multiple product categories',
            logo: 'https://example.com/multi-logo.png'
        });

        const electronicsCategory = await api.createCategory({
            categoryName: 'Electronics',
            app_id: app.id
        });

        const booksCategory = await api.createCategory({
            categoryName: 'Books',
            app_id: app.id
        });

        const clothingCategory = await api.createCategory({
            categoryName: 'Clothing',
            app_id: app.id
        });

        // Electronics products
        const laptop = await api.createProduct({
            productName: 'Gaming Laptop',
            productDescription: 'High-performance gaming laptop with RTX graphics',
            productImage: 'https://example.com/laptop.jpg',
            productPrice: 1299.99,
            productStock: 15,
            productSku: 'GL-001',
            category_id: electronicsCategory.id
        });

        const smartphone = await api.createProduct({
            productName: 'Smartphone',
            productDescription: 'Latest generation smartphone with 5G',
            productImage: 'https://example.com/smartphone.jpg',
            productPrice: 799.99,
            productStock: 30,
            productSku: 'SP-001',
            category_id: electronicsCategory.id
        });

        // Books
        const novel = await api.createProduct({
            productName: 'Programming Book',
            productDescription: 'Learn JavaScript from scratch',
            productImage: 'https://example.com/book.jpg',
            productPrice: 39.99,
            productStock: 100,
            productSku: 'PB-001',
            category_id: booksCategory.id
        });

        // Clothing
        const jacket = await api.createProduct({
            productName: 'Winter Jacket',
            productDescription: 'Warm winter jacket for cold weather',
            productImage: 'https://example.com/jacket.jpg',
            productPrice: 89.99,
            productStock: 45,
            productSku: 'WJ-001',
            category_id: clothingCategory.id
        });

        // Get all products and display them
        const allProducts = await api.getProducts();
        console.log('📋 All products by category:');
        
        allProducts.forEach(product => {
            console.log(`- ${product.productName}: $${product.productPrice} | Stock: ${product.productStock} | SKU: ${product.productSku || 'N/A'} (Category: ${product.category?.categoryName || 'Unknown'})`);
        });

        console.log('🎉 Product types demonstration completed!');

    } catch (error) {
        console.error('💥 Product types demo failed:', error.message);
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        ProductAPI, 
        demonstrateProductAPI, 
        demonstrateProductValidationErrors,
        demonstrateProductTypes 
    };
}

// Run demo if this file is executed directly
if (typeof window !== 'undefined') {
    // Browser environment
    console.log('🚀 Product API Examples loaded. Run demonstrateProductAPI() to see the demo.');
    window.ProductAPI = ProductAPI;
    window.demonstrateProductAPI = demonstrateProductAPI;
    window.demonstrateProductValidationErrors = demonstrateProductValidationErrors;
    window.demonstrateProductTypes = demonstrateProductTypes;
} else if (require.main === module) {
    // Node.js environment
    console.log('🚀 Running Product API demo...');
    demonstrateProductAPI()
        .then(() => {
            console.log('\n🚀 Running product validation errors demo...');
            return demonstrateProductValidationErrors();
        })
        .then(() => {
            console.log('\n🚀 Running product types demo...');
            return demonstrateProductTypes();
        })
        .then(() => {
            console.log('\n✨ All demos completed!');
        })
        .catch(error => {
            console.error('💥 Demo failed:', error);
        });
}
