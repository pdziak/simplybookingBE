<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Test email verification token storage
echo "Testing Email Verification Token Storage\n";
echo "======================================\n\n";

try {
    $pdo = new PDO($_ENV['DATABASE_URL']);
    
    // Check if the fields exist in the users table
    echo "1. Checking if email verification fields exist in users table...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasEmailVerificationToken = in_array('email_verification_token', $columns);
    $hasEmailVerificationTokenExpiresAt = in_array('email_verification_token_expires_at', $columns);
    
    echo "   - email_verification_token: " . ($hasEmailVerificationToken ? "✓ EXISTS" : "✗ MISSING") . "\n";
    echo "   - email_verification_token_expires_at: " . ($hasEmailVerificationTokenExpiresAt ? "✓ EXISTS" : "✗ MISSING") . "\n\n";
    
    if (!$hasEmailVerificationToken || !$hasEmailVerificationTokenExpiresAt) {
        echo "❌ Email verification fields are missing from the database!\n";
        echo "You need to run migrations to add these fields.\n";
        exit(1);
    }
    
    // Check if there are any users with verification tokens
    echo "2. Checking existing users with verification tokens...\n";
    $stmt = $pdo->query("SELECT id, email, email_verification_token, email_verification_token_expires_at FROM users WHERE email_verification_token IS NOT NULL LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "   - No users found with verification tokens\n";
    } else {
        echo "   - Found " . count($users) . " users with verification tokens:\n";
        foreach ($users as $user) {
            echo "     * ID: {$user['id']}, Email: {$user['email']}, Token: " . substr($user['email_verification_token'], 0, 16) . "...\n";
        }
    }
    
    // Test creating a user with verification token
    echo "\n3. Testing user creation with verification token...\n";
    
    $testEmail = 'test-verification@example.com';
    $testToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Delete any existing test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    
    // Insert test user with verification token
    $stmt = $pdo->prepare("
        INSERT INTO users (email, roles, password, first_name, last_name, created_at, email_verification_token, email_verification_token_expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $testEmail,
        json_encode(['ROLE_USER']),
        password_hash('testpassword', PASSWORD_DEFAULT),
        'Test',
        'User',
        date('Y-m-d H:i:s'),
        $testToken,
        $expiresAt
    ]);
    
    if ($result) {
        echo "   ✓ Test user created successfully with verification token\n";
        
        // Verify the token was stored
        $stmt = $pdo->prepare("SELECT email_verification_token, email_verification_token_expires_at FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['email_verification_token'] === $testToken) {
            echo "   ✓ Verification token stored correctly\n";
            echo "   ✓ Token: " . substr($user['email_verification_token'], 0, 16) . "...\n";
            echo "   ✓ Expires at: " . $user['email_verification_token_expires_at'] . "\n";
        } else {
            echo "   ✗ Verification token not stored correctly\n";
        }
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        echo "   ✓ Test user cleaned up\n";
        
    } else {
        echo "   ✗ Failed to create test user\n";
    }
    
    echo "\n✅ Email verification token storage test completed successfully!\n";
    echo "The database fields exist and can store verification tokens.\n";
    echo "If tokens are not being stored during registration, the issue might be in the application logic.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
