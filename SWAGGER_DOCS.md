# Authentication Endpoints in Swagger Documentation

The authentication endpoints should now appear in your Swagger documentation at `http://localhost:8000/docs` (or your configured API Platform documentation URL).

## What I've Added

I've added API Platform OpenAPI annotations to all authentication endpoints:

### 1. **POST /api/auth/register**
- **Summary**: Register a new user
- **Description**: Create a new user account with email and password
- **Request Body**: JSON with email and password
- **Responses**: 201 (success), 400 (validation), 409 (user exists)

### 2. **POST /api/auth/login**
- **Summary**: Login user
- **Description**: Authenticate user with email and password
- **Request Body**: JSON with email and password
- **Responses**: 200 (success), 400 (validation), 401 (invalid credentials)

### 3. **GET /api/auth/me**
- **Summary**: Get current user
- **Description**: Get information about the currently authenticated user
- **Security**: Requires Bearer token
- **Responses**: 200 (success), 401 (not authenticated)

### 4. **POST /api/auth/refresh**
- **Summary**: Refresh token
- **Description**: Get a new JWT token using the current valid token
- **Security**: Requires Bearer token
- **Responses**: 200 (success), 401 (not authenticated)

## How to View in Swagger

1. **Start your development server**:
   ```bash
   cd api
   php -S localhost:8000 -t public
   ```

2. **Open Swagger documentation**:
   - Go to `http://localhost:8000/docs`
   - You should see the authentication endpoints listed

3. **Test the endpoints**:
   - Use the "Try it out" button on each endpoint
   - The register and login endpoints don't require authentication
   - The /me and /refresh endpoints require a Bearer token

## JWT Authentication in Swagger

The Swagger UI now includes:
- **Security scheme**: Bearer JWT authentication
- **Authorization button**: Click the "Authorize" button in Swagger UI
- **Token input**: Enter your JWT token in the format: `Bearer your-token-here`

## Troubleshooting

If the endpoints don't appear in Swagger docs:

1. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

2. **Check API Platform configuration**:
   - Ensure `api_platform.yaml` is properly configured
   - Check that the controller is in the correct namespace

3. **Verify routes**:
   ```bash
   php bin/console debug:router | grep auth
   ```

4. **Check for errors**:
   - Look at the browser console for any JavaScript errors
   - Check the Symfony logs for any PHP errors

## Example Usage in Swagger

1. **Register a user**:
   - Go to POST /api/auth/register
   - Click "Try it out"
   - Enter: `{"email": "test@example.com", "password": "password123"}`
   - Execute and copy the returned token

2. **Authorize in Swagger**:
   - Click the "Authorize" button (lock icon)
   - Enter: `Bearer your-token-here`
   - Click "Authorize"

3. **Test protected endpoints**:
   - Now you can test GET /api/auth/me and POST /api/auth/refresh
   - These will use the Bearer token automatically

The authentication endpoints should now be fully integrated with API Platform's Swagger documentation!
