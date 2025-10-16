// Frontend Authentication Example
// This file shows how to integrate with the authentication API from your frontend

class AuthService {
    constructor(baseUrl = 'http://localhost:8000/api') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('token');
    }

    // Register a new user
    async register(userData) {
        try {
            const response = await fetch(`${this.baseUrl}/auth/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Registration failed');
            }

            const data = await response.json();
            this.setToken(data.token);
            return data;
        } catch (error) {
            console.error('Registration error:', error);
            throw error;
        }
    }

    // Login user
    async login(credentials) {
        try {
            const response = await fetch(`${this.baseUrl}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(credentials)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Login failed');
            }

            const data = await response.json();
            this.setToken(data.token);
            return data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    // Get current user
    async getCurrentUser() {
        try {
            const response = await fetch(`${this.baseUrl}/auth/me`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    this.logout();
                    throw new Error('Not authenticated');
                }
                throw new Error('Failed to get user data');
            }

            return await response.json();
        } catch (error) {
            console.error('Get current user error:', error);
            throw error;
        }
    }

    // Refresh token
    async refreshToken() {
        try {
            const response = await fetch(`${this.baseUrl}/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (!response.ok) {
                throw new Error('Token refresh failed');
            }

            const data = await response.json();
            this.setToken(data.token);
            return data;
        } catch (error) {
            console.error('Token refresh error:', error);
            this.logout();
            throw error;
        }
    }

    // Logout user
    logout() {
        this.token = null;
        localStorage.removeItem('token');
    }

    // Set token
    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
    }

    // Check if user is authenticated
    isAuthenticated() {
        return !!this.token;
    }

    // Make authenticated API requests
    async makeAuthenticatedRequest(url, options = {}) {
        if (!this.token) {
            throw new Error('Not authenticated');
        }

        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`,
                ...options.headers
            }
        });

        if (response.status === 401) {
            // Try to refresh token
            try {
                await this.refreshToken();
                // Retry the request with new token
                return this.makeAuthenticatedRequest(url, options);
            } catch (refreshError) {
                this.logout();
                throw new Error('Authentication expired');
            }
        }

        return response;
    }
}

// Usage examples:

// Initialize auth service
const auth = new AuthService();

// Register a new user
async function registerUser() {
    try {
        const userData = {
            email: 'john@example.com',
            password: 'password123'
        };
        
        const result = await auth.register(userData);
        console.log('User registered:', result);
        return result;
    } catch (error) {
        console.error('Registration failed:', error.message);
    }
}

// Login user
async function loginUser() {
    try {
        const credentials = {
            email: 'john@example.com',
            password: 'password123'
        };
        
        const result = await auth.login(credentials);
        console.log('User logged in:', result);
        return result;
    } catch (error) {
        console.error('Login failed:', error.message);
    }
}

// Get current user
async function getCurrentUser() {
    try {
        const user = await auth.getCurrentUser();
        console.log('Current user:', user);
        return user;
    } catch (error) {
        console.error('Failed to get current user:', error.message);
    }
}

// Make authenticated request to protected endpoint
async function fetchProtectedData() {
    try {
        const response = await auth.makeAuthenticatedRequest('/users');
        const data = await response.json();
        console.log('Protected data:', data);
        return data;
    } catch (error) {
        console.error('Failed to fetch protected data:', error.message);
    }
}

// Logout
function logoutUser() {
    auth.logout();
    console.log('User logged out');
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthService;
}

// Example React hook (if using React)
/*
import { useState, useEffect } from 'react';

function useAuth() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const auth = new AuthService();

    useEffect(() => {
        if (auth.isAuthenticated()) {
            auth.getCurrentUser()
                .then(user => setUser(user))
                .catch(() => auth.logout())
                .finally(() => setLoading(false));
        } else {
            setLoading(false);
        }
    }, []);

    const login = async (credentials) => {
        const result = await auth.login(credentials);
        setUser(result.user);
        return result;
    };

    const register = async (userData) => {
        const result = await auth.register(userData);
        setUser(result.user);
        return result;
    };

    const logout = () => {
        auth.logout();
        setUser(null);
    };

    return {
        user,
        loading,
        login,
        register,
        logout,
        isAuthenticated: auth.isAuthenticated()
    };
}
*/
