# Route Prefix Verification

## Current Issue

You can see in Swagger that only the `/api/auth/login` route shows the `/api` prefix, while the other routes (`/greetings`, `/users`) don't have the prefix. This is because API Platform routes are handled differently than custom controller routes.

## What I've Fixed

I've made the following changes to ensure all routes are prefixed with `/api`:

### 1. **API Platform Route Prefix**
- **File**: `api/config/routes/api_platform.yaml`
- **Change**: Added `prefix: /api` to API Platform routes
- **Result**: All API Platform entity routes should now be prefixed with `/api`

### 2. **Controller Route Prefix** (Already Done)
- **File**: `api/config/routes.yaml`
- **Change**: Added `prefix: /api` to controller routes
- **Result**: All custom controller routes are prefixed with `/api`

### 3. **API Platform Configuration**
- **File**: `api/config/packages/api_platform.yaml`
- **Change**: Added OpenAPI server configuration with `/api` base path
- **Result**: Swagger UI should show all routes with `/api` prefix

## Expected Route Structure

After these changes, all routes should be prefixed with `/api`:

### API Platform Entity Routes:
- **GET** `/api/greetings` - Get all greetings
- **POST** `/api/greetings` - Create greeting
- **GET** `/api/greetings/{id}` - Get specific greeting
- **PUT** `/api/greetings/{id}` - Update greeting
- **DELETE** `/api/greetings/{id}` - Delete greeting
- **PATCH** `/api/greetings/{id}` - Partial update greeting

- **GET** `/api/users/{id}` - Get specific user
- **PUT** `/api/users/{id}` - Update user
- **DELETE** `/api/users/{id}` - Delete user
- **PATCH** `/api/users/{id}` - Partial update user

### Custom Controller Routes:
- **POST** `/api/auth/register` - User registration
- **POST** `/api/auth/login` - User login
- **GET** `/api/auth/me` - Get current user
- **POST** `/api/auth/refresh` - Refresh token

## How to Verify

### 1. **Clear Cache and Restart Server**
```bash
cd api
php bin/console cache:clear
php -S localhost:8000 -t public
```

### 2. **Check Swagger Documentation**
- Go to `http://localhost:8000/docs`
- All routes should now show the `/api` prefix
- The server dropdown should show `/api` as the base path

### 3. **Test Routes Manually**
```bash
# Test API Platform routes
curl http://localhost:8000/api/greetings
curl http://localhost:8000/api/users

# Test custom controller routes
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

### 4. **Check Route List** (if intl extension is installed)
```bash
php bin/console debug:router | grep -E "(greetings|users|auth)"
```

## Troubleshooting

If routes still don't show the `/api` prefix:

1. **Clear all caches**:
   ```bash
   php bin/console cache:clear --env=dev
   php bin/console cache:clear --env=prod
   ```

2. **Restart the web server** completely

3. **Check browser cache** - Clear browser cache and refresh Swagger UI

4. **Verify configuration** - Make sure all route files have the correct prefix settings

## Current Status

✅ **Controller routes** - Already prefixed with `/api`  
✅ **API Platform routes** - Configuration updated to use `/api` prefix  
✅ **Swagger configuration** - Updated to show `/api` base path  
⏳ **Verification** - Waiting for intl extension installation to test

Once you install the intl extension and restart the server, all routes should be properly prefixed with `/api` in the Swagger documentation!
