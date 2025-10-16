<?php

/**
 * Simple test script to verify authentication endpoints work
 * Run with: php test_endpoints.php
 */

$baseUrl = 'http://localhost:8000/api';

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "🧪 Testing Authentication Endpoints\n";
echo "==================================\n\n";

// Test 1: Register user
echo "1. Testing user registration...\n";
$registerData = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

$registerResponse = makeRequest($baseUrl . '/auth/register', 'POST', $registerData);

if ($registerResponse['code'] === 201) {
    echo "✅ Registration successful!\n";
    $token = $registerResponse['body']['token'];
    echo "   Token: " . substr($token, 0, 20) . "...\n\n";
} else {
    echo "❌ Registration failed!\n";
    echo "   Code: " . $registerResponse['code'] . "\n";
    echo "   Response: " . json_encode($registerResponse['body']) . "\n\n";
    exit(1);
}

// Test 2: Login user
echo "2. Testing user login...\n";
$loginData = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

$loginResponse = makeRequest($baseUrl . '/auth/login', 'POST', $loginData);

if ($loginResponse['code'] === 200) {
    echo "✅ Login successful!\n";
    $loginToken = $loginResponse['body']['token'];
    echo "   Token: " . substr($loginToken, 0, 20) . "...\n\n";
} else {
    echo "❌ Login failed!\n";
    echo "   Code: " . $loginResponse['code'] . "\n";
    echo "   Response: " . json_encode($loginResponse['body']) . "\n\n";
}

// Test 3: Get current user
echo "3. Testing get current user...\n";
$meResponse = makeRequest($baseUrl . '/auth/me', 'GET', null, $token);

if ($meResponse['code'] === 200) {
    echo "✅ Get current user successful!\n";
    echo "   User email: " . $meResponse['body']['email'] . "\n";
    echo "   User ID: " . $meResponse['body']['id'] . "\n\n";
} else {
    echo "❌ Get current user failed!\n";
    echo "   Code: " . $meResponse['code'] . "\n";
    echo "   Response: " . json_encode($meResponse['body']) . "\n\n";
}

// Test 4: Refresh token
echo "4. Testing token refresh...\n";
$refreshResponse = makeRequest($baseUrl . '/auth/refresh', 'POST', null, $token);

if ($refreshResponse['code'] === 200) {
    echo "✅ Token refresh successful!\n";
    $newToken = $refreshResponse['body']['token'];
    echo "   New token: " . substr($newToken, 0, 20) . "...\n\n";
} else {
    echo "❌ Token refresh failed!\n";
    echo "   Code: " . $refreshResponse['code'] . "\n";
    echo "   Response: " . json_encode($refreshResponse['body']) . "\n\n";
}

// Test 5: Test with invalid credentials
echo "5. Testing invalid credentials...\n";
$invalidLoginData = [
    'email' => 'test@example.com',
    'password' => 'wrongpassword'
];

$invalidLoginResponse = makeRequest($baseUrl . '/auth/login', 'POST', $invalidLoginData);

if ($invalidLoginResponse['code'] === 401) {
    echo "✅ Invalid credentials properly rejected!\n";
    echo "   Response: " . json_encode($invalidLoginResponse['body']) . "\n\n";
} else {
    echo "❌ Invalid credentials test failed!\n";
    echo "   Code: " . $invalidLoginResponse['code'] . "\n";
    echo "   Response: " . json_encode($invalidLoginResponse['body']) . "\n\n";
}

echo "🎉 All tests completed!\n";
echo "\nTo start the server, run:\n";
echo "cd api && php -S localhost:8000 -t public\n";
