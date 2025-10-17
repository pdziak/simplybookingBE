# Authentication API Documentation

## Overview

This API provides JWT-based authentication for user registration and login. The endpoints are fully functional but may not appear in Swagger docs due to the missing intl extension.

## Endpoints

### 1. Register User
**POST** `/auth/register`

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
**POST** `/auth/login`

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
        "firstName": null,
        "lastName": null,
        "roles": ["ROLE_USER"]
    }
}
```

### 3. Get Current User
**GET** `/auth/me`

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
    "firstName": null,
    "lastName": null,
    "roles": ["ROLE_USER"],
    "createdAt": "2024-10-13 14:00:00",
    "updatedAt": null
}
```

### 4. Refresh Token
**POST** `/auth/refresh`

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
        "firstName": null,
        "lastName": null,
        "roles": ["ROLE_USER"]
    }
}
```

## Testing with cURL

### 1. Register a new user
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

### 2. Login user
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

### 3. Get current user (replace TOKEN with actual token)
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN"
```

### 4. Refresh token (replace TOKEN with actual token)
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Authorization: Bearer TOKEN"
```

## Testing with Postman

1. **Create a new collection** called "Authentication API"
2. **Add the 4 endpoints** with the URLs and request bodies shown above
3. **For protected endpoints** (/me, /refresh), add the Authorization header:
   - Type: Bearer Token
   - Token: [paste the token from login/register response]

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

### JavaScript Example
```javascript
// Register user
const registerUser = async (email, password) => {
    const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    });
    
    if (response.ok) {
        const data = await response.json();
        localStorage.setItem('token', data.token);
        return data;
    }
    
    throw new Error('Registration failed');
};

// Login user
const loginUser = async (email, password) => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    });
    
    if (response.ok) {
        const data = await response.json();
        localStorage.setItem('token', data.token);
        return data;
    }
    
    throw new Error('Login failed');
};

// Get current user
const getCurrentUser = async () => {
    const token = localStorage.getItem('token');
    const response = await fetch('/api/auth/me', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    if (response.ok) {
        return await response.json();
    }
    
    throw new Error('Failed to get user data');
};
```

## Database Setup

Run the migration to create the users table:

```bash
php bin/console doctrine:migrations:migrate
```

## Current Status

✅ **Authentication endpoints are fully functional**  
✅ **JWT tokens are generated and validated**  
✅ **User registration and login work**  
✅ **Protected endpoints require authentication**  
⏳ **Swagger docs not available due to missing intl extension**

The API is ready to use! You can test all endpoints using cURL, Postman, or integrate them into your frontend application.
