<?php

/**
 * Test script for Contact API endpoints
 * Run with: php test_contact_endpoints.php
 */

$baseUrl = 'http://localhost:8000/api/contact';

echo "🧪 Testing Contact API Endpoints\n";
echo "================================\n\n";

// Test data
$testData = [
    'company' => 'Test Company Sp. z o.o.',
    'email' => 'test@example.com',
    'content' => 'To jest testowe zapytanie z formularza kontaktowego. Prosimy o kontakt w sprawie oferty.'
];

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'Content-Type: application/json',
        'Accept: application/json'
    ], $headers));
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'raw_response' => $response
    ];
}

// Test 1: Submit contact form
echo "1. Testing contact form submission...\n";
$response = makeRequest($baseUrl, 'POST', $testData);

if ($response['http_code'] === 201) {
    echo "✅ Contact form submitted successfully\n";
    echo "   Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    $contactId = $response['data']['id'] ?? null;
} else {
    echo "❌ Contact form submission failed\n";
    echo "   HTTP Code: " . $response['http_code'] . "\n";
    echo "   Response: " . $response['raw_response'] . "\n";
    $contactId = null;
}

echo "\n";

// Test 2: Submit contact form with validation errors
echo "2. Testing contact form with validation errors...\n";
$invalidData = [
    'company' => '', // Empty company name
    'email' => 'invalid-email', // Invalid email
    'content' => 'Short' // Too short content
];

$response = makeRequest($baseUrl, 'POST', $invalidData);

if ($response['http_code'] === 400) {
    echo "✅ Validation errors handled correctly\n";
    echo "   Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Validation errors not handled properly\n";
    echo "   HTTP Code: " . $response['http_code'] . "\n";
    echo "   Response: " . $response['raw_response'] . "\n";
}

echo "\n";

// Test 3: Test email functionality (requires authentication)
echo "3. Testing email functionality...\n";
echo "   Note: This requires authentication token\n";
echo "   Skipping for now - would need valid JWT token\n";

echo "\n";

// Test 4: Get contact submissions (requires authentication)
echo "4. Testing get contact submissions...\n";
echo "   Note: This requires authentication token\n";
echo "   Skipping for now - would need valid JWT token\n";

echo "\n";

// Test 5: Test with different contact data
echo "5. Testing with different contact data...\n";
$testData2 = [
    'company' => 'Another Company Ltd.',
    'email' => 'contact@anothercompany.com',
    'content' => 'Dzień dobry, chciałbym zapytać o możliwość współpracy. Czy moglibyśmy umówić się na spotkanie w przyszłym tygodniu?'
];

$response = makeRequest($baseUrl, 'POST', $testData2);

if ($response['http_code'] === 201) {
    echo "✅ Second contact form submitted successfully\n";
    echo "   Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Second contact form submission failed\n";
    echo "   HTTP Code: " . $response['http_code'] . "\n";
    echo "   Response: " . $response['raw_response'] . "\n";
}

echo "\n";

// Test 6: Test with HTML content
echo "6. Testing with HTML content...\n";
$htmlData = [
    'company' => 'HTML Test Company',
    'email' => 'html@test.com',
    'content' => 'To jest test z <strong>HTML</strong> contentem. <br>Sprawdzamy czy system obsługuje HTML.'
];

$response = makeRequest($baseUrl, 'POST', $htmlData);

if ($response['http_code'] === 201) {
    echo "✅ HTML content handled successfully\n";
    echo "   Response: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ HTML content handling failed\n";
    echo "   HTTP Code: " . $response['http_code'] . "\n";
    echo "   Response: " . $response['raw_response'] . "\n";
}

echo "\n";

echo "🎉 Contact API testing completed!\n";
echo "\n";
echo "Next steps:\n";
echo "- Check database for contact_submissions table\n";
echo "- Test email functionality with proper SMTP configuration\n";
echo "- Test authenticated endpoints with valid JWT token\n";
echo "- Verify email templates are working correctly\n";
