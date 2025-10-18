<?php

// Test registration endpoint to see if email verification token is generated
$apiUrl = 'http://localhost:8000/api/auth/register';

$testData = [
    'email' => 'test@example.com',
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
    echo "Failed to connect to API\n";
    exit(1);
}

$response = json_decode($result, true);

echo "Registration Test Results:\n";
echo "========================\n";
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

if (isset($response['message']) && strpos($response['message'], 'verification') !== false) {
    echo "\n✓ Registration mentions email verification\n";
} else {
    echo "\n✗ Registration does not mention email verification\n";
}

if (isset($response['user']['emailVerified']) && $response['user']['emailVerified'] === false) {
    echo "✓ User is marked as not verified (emailVerified: false)\n";
} else {
    echo "✗ User verification status not properly set\n";
}

echo "\nNote: Check the database to see if email_verification_token was generated.\n";
echo "You can check with: SELECT email, email_verification_token, email_verification_token_expires_at FROM users WHERE email = 'test@example.com';\n";
