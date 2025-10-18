<?php

/**
 * Test script for creating HTML content
 * 
 * This script demonstrates how to create content with HTML in the description field
 */

// Base URL for the API
$baseUrl = 'http://localhost:8000/api/content';

// Sample HTML content
$htmlContent = [
    'title' => 'HTML Content Example',
    'slug' => 'html-content-example',
    'description' => '<h2>Welcome to HTML Content</h2>
<p>This is a <strong>bold paragraph</strong> with <em>italic text</em> and a <a href="https://example.com" target="_blank">link</a>.</p>

<h3>Features:</h3>
<ul>
    <li>Rich HTML formatting</li>
    <li>Lists and tables</li>
    <li>Links and images</li>
    <li>Custom styling</li>
</ul>

<blockquote>
    <p>This is a blockquote with some important information.</p>
</blockquote>

<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th>Feature</th>
            <th>Status</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>HTML Support</td>
            <td>✅ Active</td>
            <td>Full HTML rendering</td>
        </tr>
        <tr>
            <td>Security</td>
            <td>✅ Active</td>
            <td>Basic sanitization</td>
        </tr>
    </tbody>
</table>

<p>You can also include <code>inline code</code> and code blocks:</p>

<pre><code>function example() {
    return "Hello World!";
}</code></pre>',
    'isActive' => true
];

echo "HTML Content Test Script\n";
echo "========================\n\n";

// Test: Create HTML content
echo "1. Creating HTML content...\n";
$createResponse = makeRequest('POST', $baseUrl, $htmlContent);
echo "Response: " . $createResponse['response'] . "\n";
echo "Status: " . $createResponse['status'] . "\n\n";

if ($createResponse['status'] === 201) {
    $createdContent = json_decode($createResponse['response'], true);
    $contentId = $createdContent['id'];
    $slug = $createdContent['slug'];
    
    echo "2. Content created successfully!\n";
    echo "   ID: $contentId\n";
    echo "   Slug: $slug\n";
    echo "   URL: http://localhost:3000/content/$slug\n\n";
    
    // Test: Get content by slug (public endpoint)
    echo "3. Testing public endpoint...\n";
    $getResponse = makeRequest('GET', $baseUrl . '/public/slug/' . $slug);
    echo "Response: " . $getResponse['response'] . "\n";
    echo "Status: " . $getResponse['status'] . "\n\n";
}

echo "Test completed!\n";
echo "\nNext steps:\n";
echo "1. Visit http://localhost:3000/content/html-content-example to see the rendered HTML\n";
echo "2. The content will be rendered using dangerouslySetInnerHTML\n";
echo "3. HTML tags like <h2>, <p>, <strong>, <ul>, <table> will be properly rendered\n";

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: 'application/json',
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
