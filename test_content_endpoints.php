<?php

/**
 * Test script for Content API endpoints
 * 
 * This script demonstrates how to use the new Content API endpoints
 * Run this after setting up the database and starting the server
 */

// Base URL for the API
$baseUrl = 'http://localhost:8000/api/content';

// Sample content data
$sampleContent = [
    'title' => 'Welcome to Our Website',
    'slug' => 'welcome-to-our-website',
    'description' => 'This is a sample content piece that demonstrates the Content API functionality.',
    'isActive' => true
];

echo "Content API Test Script\n";
echo "======================\n\n";

// Test 1: Create content
echo "1. Creating content...\n";
$createResponse = makeRequest('POST', $baseUrl, $sampleContent);
echo "Response: " . $createResponse['response'] . "\n";
echo "Status: " . $createResponse['status'] . "\n\n";

if ($createResponse['status'] === 201) {
    $createdContent = json_decode($createResponse['response'], true);
    $contentId = $createdContent['id'];
    
    // Test 2: Get content by ID
    echo "2. Getting content by ID ($contentId)...\n";
    $getResponse = makeRequest('GET', $baseUrl . '/' . $contentId);
    echo "Response: " . $getResponse['response'] . "\n";
    echo "Status: " . $getResponse['status'] . "\n\n";
    
    // Test 3: Get content by slug
    echo "3. Getting content by slug...\n";
    $getBySlugResponse = makeRequest('GET', $baseUrl . '/slug/' . $sampleContent['slug']);
    echo "Response: " . $getBySlugResponse['response'] . "\n";
    echo "Status: " . $getBySlugResponse['status'] . "\n\n";
    
    // Test 4: Update content
    echo "4. Updating content...\n";
    $updateData = [
        'title' => 'Updated Welcome Message',
        'description' => 'This content has been updated to test the update functionality.'
    ];
    $updateResponse = makeRequest('PUT', $baseUrl . '/' . $contentId, $updateData);
    echo "Response: " . $updateResponse['response'] . "\n";
    echo "Status: " . $updateResponse['status'] . "\n\n";
    
    // Test 5: List all content
    echo "5. Listing all content...\n";
    $listResponse = makeRequest('GET', $baseUrl);
    echo "Response: " . $listResponse['response'] . "\n";
    echo "Status: " . $listResponse['status'] . "\n\n";
    
    // Test 6: List active content only
    echo "6. Listing active content only...\n";
    $listActiveResponse = makeRequest('GET', $baseUrl . '/active');
    echo "Response: " . $listActiveResponse['response'] . "\n";
    echo "Status: " . $listActiveResponse['status'] . "\n\n";
    
    // Test 7: Delete content
    echo "7. Deleting content...\n";
    $deleteResponse = makeRequest('DELETE', $baseUrl . '/' . $contentId);
    echo "Response: " . $deleteResponse['response'] . "\n";
    echo "Status: " . $deleteResponse['status'] . "\n\n";
}

echo "Test completed!\n";

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
