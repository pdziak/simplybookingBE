# Documentation Route Fix

## ✅ Fixed the `/docs` Route Issue

I've updated the API Platform route configuration to handle both the API routes and documentation routes separately.

## 🔧 Current Configuration

### API Platform Routes (`api/config/routes/api_platform.yaml`)
```yaml
# API Platform entity routes with /api prefix
api_platform_entities:
    resource: .
    type: api_platform
    prefix: /api
    requirements:
        _format: 'json|jsonld'

# API Platform documentation routes without prefix
api_platform_docs:
    resource: .
    type: api_platform
    prefix: /
    requirements:
        _format: 'html'
```

### Controller Routes (`api/config/routes.yaml`)
```yaml
controllers:
    resource:
        path: ../src/Controller/
        namespace: App\Controller
    type: attribute
    prefix: /api
```

## 📋 Expected Route Structure

### Documentation Routes (No Prefix):
- `GET /docs` - Swagger UI documentation
- `GET /docs.json` - OpenAPI JSON specification

### API Entity Routes (With /api Prefix):
- `GET /api/greetings` - Get all greetings
- `POST /api/greetings` - Create greeting
- `GET /api/greetings/{id}` - Get specific greeting
- `PUT /api/greetings/{id}` - Update greeting
- `DELETE /api/greetings/{id}` - Delete greeting
- `PATCH /api/greetings/{id}` - Partial update greeting

- `GET /api/users/{id}` - Get specific user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `PATCH /api/users/{id}` - Partial update user

### Custom Controller Routes (With /api Prefix):
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/auth/me` - Get current user
- `POST /api/auth/refresh` - Refresh token

## 🚀 How to Access

### 1. **Swagger Documentation**
- **URL**: `http://localhost:8000/docs`
- **Description**: Interactive API documentation
- **Features**: Try out endpoints, see request/response schemas

### 2. **OpenAPI JSON**
- **URL**: `http://localhost:8000/docs.json`
- **Description**: Raw OpenAPI specification
- **Use**: For importing into other tools

### 3. **API Endpoints**
- **Base URL**: `http://localhost:8000/api`
- **Description**: All API endpoints for data operations

## 🧪 Testing

### 1. **Start the Server**
```bash
cd api
php -S localhost:8000 -t public
```

### 2. **Test Documentation**
```bash
# Test Swagger UI
curl http://localhost:8000/docs

# Test OpenAPI JSON
curl http://localhost:8000/docs.json
```

### 3. **Test API Endpoints**
```bash
# Test API Platform routes
curl http://localhost:8000/api/greetings
curl http://localhost:8000/api/users

# Test custom controller routes
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

## ✅ Benefits

- **Clean Documentation URL** - `/docs` is easy to remember and access
- **Prefixed API Routes** - All API endpoints use `/api` prefix for consistency
- **Proper Separation** - Documentation and API routes are handled separately
- **RESTful Convention** - Follows common API design patterns

## 🎯 Expected Result

After installing the intl extension and restarting the server:

1. **`http://localhost:8000/docs`** - Swagger UI with all routes properly prefixed
2. **`http://localhost:8000/api/*`** - All API endpoints working with `/api` prefix
3. **Consistent routing** - Clean separation between documentation and API routes

The documentation route is now properly configured and should work once the intl extension is installed!
