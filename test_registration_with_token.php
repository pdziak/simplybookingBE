<?php

// Test registration endpoint to verify email verification token is stored
$apiUrl = 'http://localhost:8000/api/auth/register';

$testData = [
    'email' => 'test-token@example.com',
    'password' => 'testpassword123',
    'firstName' => 'Test',
    'lastName' => 'User'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($testData)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($apiUrl, false, $context);

if ($result === FALSE) {
    echo "❌ Failed to connect to API\n";
    exit(1);
}

$response = json_decode($result, true);

echo "Registration Test Results:\n";
echo "========================\n";
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

if (isset($response['message']) && strpos($response['message'], 'utworzone') !== false) {
    echo "✅ Registration successful (Polish message detected)\n";
} else {
    echo "❌ Registration failed or message not in Polish\n";
}

if (isset($response['user']['emailVerified']) && $response['user']['emailVerified'] === false) {
    echo "✅ User is marked as not verified (emailVerified: false)\n";
} else {
    echo "❌ User verification status not properly set\n";
}

echo "\nNext steps:\n";
echo "1. Check the database to see if email_verification_token was generated\n";
echo "2. You can check with: SELECT email, email_verification_token, email_verification_token_expires_at FROM users WHERE email = 'test-token@example.com';\n";
echo "3. If the token exists, the fix is working correctly\n";
echo "4. If the token is NULL, there might be a database schema issue\n";
