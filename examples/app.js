/**
 * App API Usage Examples
 * 
 * This file demonstrates how to use the App API endpoint (/api/apps)
 * with JWT authentication.
 */

const API_BASE_URL = 'http://localhost/api';
const AUTH_ENDPOINT = `${API_BASE_URL}/auth`;

class AppAPI {
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
     * Create a new app
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
                throw new Error(`Failed to create app: ${error.message || response.status}`);
            }

            const app = await response.json();
            console.log('✅ App created successfully:', app);
            return app;
        } catch (error) {
            console.error('❌ Failed to create app:', error.message);
            throw error;
        }
    }

    /**
     * Get all apps
     */
    async getApps() {
        try {
            const response = await fetch(`${API_BASE_URL}/apps`, {
                method: 'GET',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch apps: ${response.status}`);
            }

            const data = await response.json();
            console.log('✅ Apps fetched successfully:', data);
            return data;
        } catch (error) {
            console.error('❌ Failed to fetch apps:', error.message);
            throw error;
        }
    }

    /**
     * Get a specific app by ID
     */
    async getApp(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/apps/${id}`, {
                method: 'GET',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch app: ${response.status}`);
            }

            const app = await response.json();
            console.log('✅ App fetched successfully:', app);
            return app;
        } catch (error) {
            console.error('❌ Failed to fetch app:', error.message);
            throw error;
        }
    }

    /**
     * Update an app
     */
    async updateApp(id, appData) {
        try {
            const response = await fetch(`${API_BASE_URL}/apps/${id}`, {
                method: 'PUT',
                headers: this.getAuthHeaders(),
                body: JSON.stringify(appData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to update app: ${error.message || response.status}`);
            }

            const app = await response.json();
            console.log('✅ App updated successfully:', app);
            return app;
        } catch (error) {
            console.error('❌ Failed to update app:', error.message);
            throw error;
        }
    }

    /**
     * Delete an app
     */
    async deleteApp(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/apps/${id}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Failed to delete app: ${response.status}`);
            }

            console.log('✅ App deleted successfully');
            return true;
        } catch (error) {
            console.error('❌ Failed to delete app:', error.message);
            throw error;
        }
    }

    /**
     * Upload logo
     */
    async uploadLogo(file) {
        try {
            const formData = new FormData();
            formData.append('logo', file);

            const response = await fetch(`${API_BASE_URL}/upload/logo`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Failed to upload logo: ${error.message || response.status}`);
            }

            const result = await response.json();
            console.log('✅ Logo uploaded successfully:', result);
            return result;
        } catch (error) {
            console.error('❌ Failed to upload logo:', error.message);
            throw error;
        }
    }
}

// Example usage
async function demonstrateAppAPI() {
    const api = new AppAPI();

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

        // Step 3: Create a new app with all fields
        console.log('📱 Creating app with all fields...');
        const app1 = await api.createApp({
            title: 'My Awesome App',
            slug: 'my-awesome-app',
            companyName: 'Awesome Company Inc.',
            email: 'contact@awesomecompany.com',
            description: 'This is an amazing app that does incredible things!',
            logo: 'https://example.com/logo.png'
        });

        // Example of uploading a logo (you would need a real file input)
        // const fileInput = document.getElementById('logoFile');
        // if (fileInput && fileInput.files[0]) {
        //     const uploadResult = await api.uploadLogo(fileInput.files[0]);
        //     console.log('Uploaded logo URL:', uploadResult.url);
        // }

        // Step 4: Create a minimal app (only required fields)
        console.log('📱 Creating minimal app...');
        const app2 = await api.createApp({
            title: 'Simple App',
            slug: 'simple-app',
            companyName: 'Simple Corp',
            email: 'hello@simplecorp.com',
            logo: 'https://example.com/simple-logo.png'
        });

        // Step 5: Get all apps
        console.log('📋 Fetching all apps...');
        const apps = await api.getApps();
        console.log(`Found ${apps.totalItems} apps`);

        // Step 6: Get a specific app
        console.log('🔍 Fetching specific app...');
        const specificApp = await api.getApp(app1.id);

        // Step 7: Update an app
        console.log('✏️ Updating app...');
        const updatedApp = await api.updateApp(app1.id, {
            title: 'My UPDATED Awesome App',
            slug: 'my-updated-awesome-app',
            companyName: 'Updated Awesome Company Inc.',
            email: 'updated@awesomecompany.com',
            description: 'This is an UPDATED amazing app that does even more incredible things!',
            logo: 'https://example.com/updated-logo.png'
        });

        // Step 8: Delete an app
        console.log('🗑️ Deleting app...');
        await api.deleteApp(app2.id);

        // Step 9: Get apps again to confirm deletion
        console.log('📋 Fetching apps after deletion...');
        const finalApps = await api.getApps();
        console.log(`Now we have ${finalApps.totalItems} apps`);

        console.log('🎉 All operations completed successfully!');

    } catch (error) {
        console.error('💥 Demo failed:', error.message);
    }
}

// Example of error handling for validation
async function demonstrateValidationErrors() {
    const api = new AppAPI();

    try {
        await api.authenticate('demo@example.com', 'password123');

        console.log('🚫 Testing validation errors...');

        // Test 1: Missing required fields
        try {
            await api.createApp({
                title: 'Incomplete App'
                // Missing slug, companyName, email, logo
            });
        } catch (error) {
            console.log('✅ Caught missing required fields error:', error.message);
        }

        // Test 2: Invalid email format
        try {
            await api.createApp({
                title: 'Invalid Email App',
                slug: 'invalid-email-app',
                companyName: 'Invalid Corp',
                email: 'not-an-email',
                logo: 'logos/logo.png'
            });
        } catch (error) {
            console.log('✅ Caught invalid email error:', error.message);
        }

        // Test 3: Invalid slug format
        try {
            await api.createApp({
                title: 'Invalid Slug App',
                slug: 'Invalid Slug!',
                companyName: 'Invalid Corp',
                email: 'test@example.com',
                logo: 'logos/logo.png'
            });
        } catch (error) {
            console.log('✅ Caught invalid slug error:', error.message);
        }

        // Test 4: Invalid logo URL
        try {
            await api.createApp({
                title: 'Invalid Logo App',
                slug: 'invalid-logo-app',
                companyName: 'Invalid Corp',
                email: 'test@example.com',
                logo: 'not-a-url'
            });
        } catch (error) {
            console.log('✅ Caught invalid logo URL error:', error.message);
        }

    } catch (error) {
        console.error('💥 Validation demo failed:', error.message);
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AppAPI, demonstrateAppAPI, demonstrateValidationErrors };
}

// Run demo if this file is executed directly
if (typeof window !== 'undefined') {
    // Browser environment
    console.log('🚀 App API Examples loaded. Run demonstrateAppAPI() to see the demo.');
    window.AppAPI = AppAPI;
    window.demonstrateAppAPI = demonstrateAppAPI;
    window.demonstrateValidationErrors = demonstrateValidationErrors;
} else if (require.main === module) {
    // Node.js environment
    console.log('🚀 Running App API demo...');
    demonstrateAppAPI()
        .then(() => {
            console.log('\n🚀 Running validation errors demo...');
            return demonstrateValidationErrors();
        })
        .then(() => {
            console.log('\n✨ All demos completed!');
        })
        .catch(error => {
            console.error('💥 Demo failed:', error);
        });
}
