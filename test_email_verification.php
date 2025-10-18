<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Test email verification functionality
echo "Testing Email Verification System\n";
echo "================================\n\n";

// Test 1: Check if email verification fields exist in database
echo "1. Checking database schema...\n";

try {
    $pdo = new PDO($_ENV['DATABASE_URL']);
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasEmailVerificationToken = in_array('email_verification_token', $columns);
    $hasEmailVerificationTokenExpiresAt = in_array('email_verification_token_expires_at', $columns);
    $hasEmailVerifiedAt = in_array('email_verified_at', $columns);
    
    echo "   - email_verification_token: " . ($hasEmailVerificationToken ? "✓ EXISTS" : "✗ MISSING") . "\n";
    echo "   - email_verification_token_expires_at: " . ($hasEmailVerificationTokenExpiresAt ? "✓ EXISTS" : "✗ MISSING") . "\n";
    echo "   - email_verified_at: " . ($hasEmailVerifiedAt ? "✓ EXISTS" : "✗ MISSING") . "\n";
    
    if ($hasEmailVerificationToken && $hasEmailVerificationTokenExpiresAt && $hasEmailVerifiedAt) {
        echo "   ✓ All required fields exist in database\n\n";
    } else {
        echo "   ✗ Some required fields are missing from database\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check if we can create a test user with verification token
echo "2. Testing user creation with verification token...\n";

try {
    $pdo = new PDO($_ENV['DATABASE_URL']);
    
    // Create a test user
    $testEmail = 'test@example.com';
    $testToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // First, delete any existing test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    
    // Insert test user
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
            echo "   ✓ Token expires at: " . $user['email_verification_token_expires_at'] . "\n";
        } else {
            echo "   ✗ Verification token not stored correctly\n";
        }
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        echo "   ✓ Test user cleaned up\n\n";
        
    } else {
        echo "   ✗ Failed to create test user\n\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Test user creation failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Check email configuration
echo "3. Checking email configuration...\n";

$mailerDsn = $_ENV['MAILER_DSN'] ?? null;
$appUrl = $_ENV['APP_URL'] ?? null;

echo "   - MAILER_DSN: " . ($mailerDsn ? "✓ SET" : "✗ NOT SET") . "\n";
echo "   - APP_URL: " . ($appUrl ? "✓ SET" : "✗ NOT SET") . "\n";

if ($mailerDsn && $appUrl) {
    echo "   ✓ Email configuration looks good\n\n";
} else {
    echo "   ✗ Email configuration incomplete\n\n";
}

echo "Email Verification System Test Complete\n";
echo "=====================================\n";
echo "If all tests passed, the email verification system should be working.\n";
echo "The issue might be with the email sending configuration (MAILER_DSN).\n";
echo "Check your email logs for any sending errors.\n";
