# Authentication API

This API provides JWT-based authentication for the frontend application.

## Endpoints

### 1. Register User
**POST** `/api/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response (201 Created):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "firstName": null,
        "lastName": null,
        "roles": ["ROLE_USER"]
    }
}
```

### 2. Login User
**POST** `/api/auth/login`

Authenticate an existing user.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "roles": ["ROLE_USER"]
    }
}
```

### 3. Get Current User
**GET** `/api/auth/me`

Get information about the currently authenticated user.

**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response (200 OK):**
```json
{
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"],
    "createdAt": "2024-10-13 14:00:00",
    "updatedAt": null
}
```

### 4. Refresh Token
**POST** `/api/auth/refresh`

Get a new JWT token using the current valid token.

**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response (200 OK):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "roles": ["ROLE_USER"]
    }
}
```

## Error Responses

### 400 Bad Request
```json
{
    "error": "Validation failed",
    "details": "email: This value should not be blank."
}
```

### 401 Unauthorized
```json
{
    "error": "Invalid credentials"
}
```

### 409 Conflict
```json
{
    "error": "User with this email already exists"
}
```

## Frontend Integration

### 1. Register a new user
```javascript
const registerUser = async (userData) => {
    const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    });
    
    if (response.ok) {
        const data = await response.json();
        // Store token in localStorage or secure storage
        localStorage.setItem('token', data.token);
        return data;
    }
    
    throw new Error('Registration failed');
};
```

### 2. Login user
```javascript
const loginUser = async (credentials) => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials)
    });
    
    if (response.ok) {
        const data = await response.json();
        // Store token in localStorage or secure storage
        localStorage.setItem('token', data.token);
        return data;
    }
    
    throw new Error('Login failed');
};
```

### 3. Make authenticated requests
```javascript
const makeAuthenticatedRequest = async (url, options = {}) => {
    const token = localStorage.getItem('token');
    
    const response = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        }
    });
    
    if (response.status === 401) {
        // Token expired or invalid, redirect to login
        localStorage.removeItem('token');
        window.location.href = '/login';
        return;
    }
    
    return response;
};
```

### 4. Get current user
```javascript
const getCurrentUser = async () => {
    const response = await makeAuthenticatedRequest('/api/auth/me');
    
    if (response.ok) {
        return await response.json();
    }
    
    throw new Error('Failed to get user data');
};
```

## Security Notes

1. **JWT Token Expiration**: Tokens expire after 1 hour (3600 seconds). Implement token refresh logic in your frontend.

2. **Password Requirements**: Minimum 6 characters (can be customized in `RegisterRequest.php`).

3. **HTTPS**: Always use HTTPS in production to protect JWT tokens in transit.

4. **Token Storage**: Store JWT tokens securely. Consider using httpOnly cookies for better security.

5. **CORS**: Configure CORS properly in `nelmio_cors.yaml` for your frontend domain.

## Database Setup

Run the migration to create the users table:

```bash
php bin/console doctrine:migrations:migrate
```

## Testing

Run the authentication tests:

```bash
php bin/phpunit tests/Api/AuthTest.php
```
