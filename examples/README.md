# App API Examples

This directory contains examples demonstrating how to use the App API endpoint (`/api/apps`) with JWT authentication.

## Files

- **`app.js`** - Node.js/browser JavaScript examples with comprehensive API usage
- **`app.html`** - Interactive HTML page for testing the API in a browser
- **`README.md`** - This documentation file

## Prerequisites

1. Make sure the API server is running:
   ```bash
   docker-compose up -d
   ```

2. The API should be accessible at `http://localhost/api`

## Quick Start

### Option 1: Interactive HTML Page

1. Open `app.html` in your web browser
2. Click "Register" to create a new user account
3. Click "Login" to authenticate
4. Use the form to create, read, update, and delete apps
5. Test validation errors using the validation examples

### Option 2: JavaScript Module

```javascript
// Include the app.js file in your project
const { AppAPI, demonstrateAppAPI } = require('./app.js');

// Create an API instance
const api = new AppAPI();

// Authenticate
await api.authenticate('your-email@example.com', 'your-password');

// Create an app
const app = await api.createApp({
    title: 'My App',
    slug: 'my-app',
    companyName: 'My Company',
    email: 'contact@mycompany.com',
    description: 'A great app!',
    logo: 'logos/logo.png'
});

// Get all apps
const apps = await api.getApps();
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token

### App Management
- `GET /api/apps` - Get all apps
- `POST /api/apps` - Create a new app
- `GET /api/apps/{id}` - Get a specific app
- `PUT /api/apps/{id}` - Update an app
- `DELETE /api/apps/{id}` - Delete an app

## App Entity Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | string | ✅ | App title |
| `slug` | string | ✅ | URL-friendly identifier (lowercase, numbers, hyphens only) |
| `companyName` | string | ✅ | Company name |
| `email` | string | ✅ | Contact email (must be valid email format) |
| `description` | string | ❌ | Optional description |
| `logo` | string | ✅ | Logo path (relative path like 'logos/filename.png') |

## Example Requests

### Create App
```javascript
const appData = {
    title: 'My Awesome App',
    slug: 'my-awesome-app',
    companyName: 'Awesome Company Inc.',
    email: 'contact@awesomecompany.com',
    description: 'This is an amazing app!',
    logo: 'logos/logo.png'
};

const app = await api.createApp(appData);
```

### Update App
```javascript
const updatedData = {
    title: 'Updated App Title',
    slug: 'updated-app-slug',
    companyName: 'Updated Company',
    email: 'updated@company.com',
    description: 'Updated description',
    logo: 'https://example.com/new-logo.png'
};

const updatedApp = await api.updateApp(appId, updatedData);
```

## Validation Examples

The API includes comprehensive validation:

### Required Fields
All fields except `description` are required. Missing required fields will return a 422 error.

### Email Validation
Email must be in valid email format:
```javascript
// ❌ Invalid
email: 'not-an-email'

// ✅ Valid
email: 'user@example.com'
```

### Slug Validation
Slug must contain only lowercase letters, numbers, and hyphens:
```javascript
// ❌ Invalid
slug: 'Invalid Slug!'
slug: 'UPPERCASE'
slug: 'with spaces'

// ✅ Valid
slug: 'valid-slug'
slug: 'my-app-123'
```

### Logo Path Validation
Logo must be a valid logo path:
```javascript
// ❌ Invalid
logo: 'not-a-path'
logo: 'https://example.com/logo.png'
logo: 'uploads/logos/logo.png'

// ✅ Valid
logo: 'logos/logo.png'
```

## Error Handling

All API methods throw errors that should be caught:

```javascript
try {
    const app = await api.createApp(appData);
    console.log('App created:', app);
} catch (error) {
    console.error('Failed to create app:', error.message);
}
```

## Authentication

The API uses JWT (JSON Web Token) authentication:

1. Register a new user or use existing credentials
2. Login to get a JWT token
3. Include the token in the `Authorization` header for all API requests

```javascript
// Login
const token = await api.authenticate('email@example.com', 'password');

// Token is automatically included in subsequent requests
const apps = await api.getApps();
```

## Running the Examples

### Node.js
```bash
node app.js
```

### Browser
1. Open `app.html` in your browser
2. Make sure the API server is running
3. Use the interactive interface to test the API

## Troubleshooting

### Common Issues

1. **401 Unauthorized**: Make sure you're logged in and the token is valid
2. **422 Validation Error**: Check that all required fields are provided and valid
3. **500 Internal Server Error**: There may be a server-side issue with API Platform compatibility
4. **CORS Error**: Make sure the API server is running and accessible

### Debug Mode

Enable debug logging by opening browser developer tools and checking the console for detailed error messages.

## API Platform Features

The App API automatically provides:

- **Pagination**: Use query parameters like `?page=1&itemsPerPage=10`
- **Filtering**: Use query parameters like `?title=MyApp`
- **Sorting**: Use query parameters like `?order[title]=asc`
- **JSON-LD**: Default response format with Hydra context
- **OpenAPI Documentation**: Available at `/api/docs`

## Example Response

```json
{
    "@context": "/api/contexts/App",
    "@id": "/api/apps/1",
    "@type": "App",
    "id": 1,
    "title": "My Awesome App",
    "slug": "my-awesome-app",
    "companyName": "Awesome Company Inc.",
    "email": "contact@awesomecompany.com",
    "description": "This is an amazing app!",
    "logo": "https://example.com/logo.png",
    "createdAt": "2025-10-14T08:30:00+00:00",
    "updatedAt": null
}
```
