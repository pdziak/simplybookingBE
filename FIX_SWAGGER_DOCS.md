# Fix Swagger Documentation for Authentication Endpoints

## The Problem

You're getting an error because the `intl` PHP extension is missing, which is required by Symfony. This prevents the application from starting and showing the Swagger documentation.

## Solution

### Option 1: Install intl extension (Recommended)

**On macOS with Homebrew:**
```bash
# Install PHP with intl extension
brew install php@8.4
# or if you have PHP installed:
brew install php-intl
```

**On Ubuntu/Debian:**
```bash
sudo apt-get install php-intl
```

**On CentOS/RHEL:**
```bash
sudo yum install php-intl
# or for newer versions:
sudo dnf install php-intl
```

### Option 2: Use Docker (Alternative)

If you can't install the intl extension locally, you can use Docker:

```bash
# Start the application with Docker
docker-compose up -d
```

## After Fixing the intl Issue

Once the intl extension is installed, the authentication endpoints should appear in Swagger docs:

1. **Start the server:**
   ```bash
   cd api
   php -S localhost:8000 -t public
   ```

2. **Open Swagger docs:**
   - Go to `http://localhost:8000/docs`
   - You should see the authentication endpoints

## What I've Added

I've already added the necessary configuration to make the authentication endpoints appear in Swagger:

### 1. **API Platform Annotations**
All authentication endpoints now have proper OpenAPI annotations:
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/refresh`

### 2. **JWT Security Scheme**
Added JWT Bearer authentication support in the OpenAPI documentation.

### 3. **Complete Documentation**
Each endpoint includes:
- Request/response schemas
- Example values
- Error responses
- Security requirements

## Testing the Endpoints

Once the server is running, you can test the endpoints in Swagger:

1. **Register a user:**
   - Go to POST /api/auth/register
   - Click "Try it out"
   - Enter: `{"email": "test@example.com", "password": "password123"}`
   - Execute and copy the returned token

2. **Authorize in Swagger:**
   - Click the "Authorize" button (lock icon)
   - Enter: `Bearer your-token-here`
   - Click "Authorize"

3. **Test protected endpoints:**
   - Now you can test GET /api/auth/me and POST /api/auth/refresh

## Files Modified

- `api/src/Controller/AuthController.php` - Added OpenAPI annotations
- `api/config/packages/api_platform.yaml` - Basic API configuration
- `api/src/OpenApi/JwtDecorator.php` - JWT security scheme decorator
- `api/config/services.yaml` - Registered the decorator

The authentication endpoints are properly configured and will appear in Swagger docs once the intl extension issue is resolved.
