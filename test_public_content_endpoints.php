<?php

/**
 * Test script for Public Content API endpoints
 * 
 * This script tests the public endpoints that don't require authentication
 */

// Base URL for the API
$baseUrl = 'http://localhost:8000/api/content';

echo "Public Content API Test Script\n";
echo "==============================\n\n";

// Test 1: Get all public content
echo "1. Getting all public content...\n";
$publicListResponse = makeRequest('GET', $baseUrl . '/public');
echo "Response: " . $publicListResponse['response'] . "\n";
echo "Status: " . $publicListResponse['status'] . "\n\n";

// Test 2: Get content by slug (public)
echo "2. Getting content by slug 'rules' (public endpoint)...\n";
$publicSlugResponse = makeRequest('GET', $baseUrl . '/public/slug/rules');
echo "Response: " . $publicSlugResponse['response'] . "\n";
echo "Status: " . $publicSlugResponse['status'] . "\n\n";

// Test 3: Try to get content by slug (authenticated endpoint) - should fail
echo "3. Trying authenticated endpoint (should fail with 401)...\n";
$authSlugResponse = makeRequest('GET', $baseUrl . '/slug/rules');
echo "Response: " . $authSlugResponse['response'] . "\n";
echo "Status: " . $authSlugResponse['status'] . "\n\n";

// Test 4: Try to get all content (authenticated endpoint) - should fail
echo "4. Trying authenticated list endpoint (should fail with 401)...\n";
$authListResponse = makeRequest('GET', $baseUrl);
echo "Response: " . $authListResponse['response'] . "\n";
echo "Status: " . $authListResponse['status'] . "\n\n";

echo "Test completed!\n";
echo "\nSummary:\n";
echo "- Public endpoints should work without authentication\n";
echo "- Authenticated endpoints should return 401 Unauthorized\n";
echo "- Only active content should be returned by public endpoints\n";

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => $response,
        'status' => $httpCode
    ];
}
