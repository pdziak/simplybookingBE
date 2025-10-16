#!/bin/bash

# App API cURL Examples
# This script demonstrates how to use the App API using cURL commands

API_BASE_URL="http://localhost/api"
AUTH_ENDPOINT="$API_BASE_URL/auth"
APPS_ENDPOINT="$API_BASE_URL/apps"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 App API cURL Examples${NC}"
echo "=================================="

# Function to print section headers
print_section() {
    echo -e "\n${YELLOW}📋 $1${NC}"
    echo "----------------------------------------"
}

# Function to print success/error messages
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Step 1: Register a new user
print_section "Step 1: Register User"
echo "Registering user: demo@example.com"

REGISTER_RESPONSE=$(curl -s -X POST "$AUTH_ENDPOINT/register" \
    -H "Content-Type: application/json" \
    -d '{
        "email": "demo@example.com",
        "password": "password123",
        "firstName": "Demo",
        "lastName": "User"
    }')

if echo "$REGISTER_RESPONSE" | grep -q "error"; then
    print_info "User might already exist, continuing..."
else
    print_success "User registered successfully"
fi

# Step 2: Login and get JWT token
print_section "Step 2: Login and Get JWT Token"
echo "Logging in..."

LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_ENDPOINT/login" \
    -H "Content-Type: application/json" \
    -d '{
        "email": "demo@example.com",
        "password": "password123"
    }')

if echo "$LOGIN_RESPONSE" | grep -q "token"; then
    JWT_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    print_success "Login successful"
    echo "JWT Token: ${JWT_TOKEN:0:50}..."
else
    print_error "Login failed"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
fi

# Step 3: Create a new app
print_section "Step 3: Create App"
echo "Creating app with all fields..."

CREATE_RESPONSE=$(curl -s -X POST "$APPS_ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d '{
        "title": "My Awesome App",
        "slug": "my-awesome-app",
        "companyName": "Awesome Company Inc.",
        "email": "contact@awesomecompany.com",
        "description": "This is an amazing app that does incredible things!",
        "logo": "https://example.com/logo.png"
    }')

if echo "$CREATE_RESPONSE" | grep -q "title"; then
    APP_ID=$(echo "$CREATE_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    print_success "App created successfully"
    echo "App ID: $APP_ID"
    echo "Response: $CREATE_RESPONSE"
else
    print_error "Failed to create app"
    echo "Response: $CREATE_RESPONSE"
fi

# Step 4: Create a minimal app (only required fields)
print_section "Step 4: Create Minimal App"
echo "Creating minimal app with only required fields..."

MINIMAL_RESPONSE=$(curl -s -X POST "$APPS_ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d '{
        "title": "Simple App",
        "slug": "simple-app",
        "companyName": "Simple Corp",
        "email": "hello@simplecorp.com",
        "logo": "https://example.com/simple-logo.png"
    }')

if echo "$MINIMAL_RESPONSE" | grep -q "title"; then
    MINIMAL_APP_ID=$(echo "$MINIMAL_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    print_success "Minimal app created successfully"
    echo "Minimal App ID: $MINIMAL_APP_ID"
else
    print_error "Failed to create minimal app"
    echo "Response: $MINIMAL_RESPONSE"
fi

# Step 5: Get all apps
print_section "Step 5: Get All Apps"
echo "Fetching all apps..."

GET_ALL_RESPONSE=$(curl -s -X GET "$APPS_ENDPOINT" \
    -H "Authorization: Bearer $JWT_TOKEN")

if echo "$GET_ALL_RESPONSE" | grep -q "member"; then
    print_success "Apps fetched successfully"
    echo "Response: $GET_ALL_RESPONSE"
else
    print_error "Failed to fetch apps"
    echo "Response: $GET_ALL_RESPONSE"
fi

# Step 6: Get specific app (if we have an ID)
if [ ! -z "$APP_ID" ]; then
    print_section "Step 6: Get Specific App"
    echo "Fetching app with ID: $APP_ID"
    
    GET_ONE_RESPONSE=$(curl -s -X GET "$APPS_ENDPOINT/$APP_ID" \
        -H "Authorization: Bearer $JWT_TOKEN")
    
    if echo "$GET_ONE_RESPONSE" | grep -q "title"; then
        print_success "App fetched successfully"
        echo "Response: $GET_ONE_RESPONSE"
    else
        print_error "Failed to fetch app"
        echo "Response: $GET_ONE_RESPONSE"
    fi
fi

# Step 7: Update app (if we have an ID)
if [ ! -z "$APP_ID" ]; then
    print_section "Step 7: Update App"
    echo "Updating app with ID: $APP_ID"
    
    UPDATE_RESPONSE=$(curl -s -X PUT "$APPS_ENDPOINT/$APP_ID" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -d '{
            "title": "My UPDATED Awesome App",
            "slug": "my-updated-awesome-app",
            "companyName": "Updated Awesome Company Inc.",
            "email": "updated@awesomecompany.com",
            "description": "This is an UPDATED amazing app that does even more incredible things!",
            "logo": "https://example.com/updated-logo.png"
        }')
    
    if echo "$UPDATE_RESPONSE" | grep -q "UPDATED"; then
        print_success "App updated successfully"
        echo "Response: $UPDATE_RESPONSE"
    else
        print_error "Failed to update app"
        echo "Response: $UPDATE_RESPONSE"
    fi
fi

# Step 8: Test validation errors
print_section "Step 8: Test Validation Errors"

echo "Testing missing required fields..."
VALIDATION_RESPONSE1=$(curl -s -X POST "$APPS_ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d '{
        "title": "Incomplete App"
    }')

if echo "$VALIDATION_RESPONSE1" | grep -q "error\|violation"; then
    print_success "Caught missing required fields error"
    echo "Response: $VALIDATION_RESPONSE1"
else
    print_error "Expected validation error for missing fields"
fi

echo -e "\nTesting invalid email..."
VALIDATION_RESPONSE2=$(curl -s -X POST "$APPS_ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d '{
        "title": "Invalid Email App",
        "slug": "invalid-email-app",
        "companyName": "Invalid Corp",
        "email": "not-an-email",
        "logo": "https://example.com/logo.png"
    }')

if echo "$VALIDATION_RESPONSE2" | grep -q "error\|violation"; then
    print_success "Caught invalid email error"
    echo "Response: $VALIDATION_RESPONSE2"
else
    print_error "Expected validation error for invalid email"
fi

echo -e "\nTesting invalid slug..."
VALIDATION_RESPONSE3=$(curl -s -X POST "$APPS_ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d '{
        "title": "Invalid Slug App",
        "slug": "Invalid Slug!",
        "companyName": "Invalid Corp",
        "email": "test@example.com",
        "logo": "https://example.com/logo.png"
    }')

if echo "$VALIDATION_RESPONSE3" | grep -q "error\|violation"; then
    print_success "Caught invalid slug error"
    echo "Response: $VALIDATION_RESPONSE3"
else
    print_error "Expected validation error for invalid slug"
fi

# Step 9: Delete app (if we have an ID)
if [ ! -z "$MINIMAL_APP_ID" ]; then
    print_section "Step 9: Delete App"
    echo "Deleting minimal app with ID: $MINIMAL_APP_ID"
    
    DELETE_RESPONSE=$(curl -s -X DELETE "$APPS_ENDPOINT/$MINIMAL_APP_ID" \
        -H "Authorization: Bearer $JWT_TOKEN")
    
    if [ $? -eq 0 ]; then
        print_success "App deleted successfully"
    else
        print_error "Failed to delete app"
        echo "Response: $DELETE_RESPONSE"
    fi
fi

# Step 10: Final check
print_section "Step 10: Final Check"
echo "Fetching apps after operations..."

FINAL_RESPONSE=$(curl -s -X GET "$APPS_ENDPOINT" \
    -H "Authorization: Bearer $JWT_TOKEN")

if echo "$FINAL_RESPONSE" | grep -q "member"; then
    print_success "Final check completed"
    echo "Response: $FINAL_RESPONSE"
else
    print_error "Final check failed"
    echo "Response: $FINAL_RESPONSE"
fi

echo -e "\n${GREEN}🎉 All cURL examples completed!${NC}"
echo "=================================="
