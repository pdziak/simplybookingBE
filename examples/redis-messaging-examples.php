<?php

/**
 * Redis Messaging Examples for Symfony Messenger
 * 
 * This file demonstrates various ways to add messages to Redis using Symfony Messenger
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Message\CreateSubdomainMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

// Example 1: Using Console Command
echo "=== Example 1: Using Console Command ===\n";
echo "Command: php bin/console app:send-notification user@example.com 'Hello World!'\n";
echo "Command with options: php bin/console app:send-notification user@example.com 'Hello World!' --type=warning --metadata='{\"priority\":\"high\"}'\n\n";

// Example 2: Direct Redis Commands
echo "=== Example 2: Direct Redis Commands ===\n";
echo "# Connect to Redis container\n";
echo "docker exec -it apip-redis redis-cli\n\n";

echo "# Add a message directly to Redis (for testing)\n";
echo "LPUSH messenger_messages '{\"body\":\"{\\\"content\\\":\\\"Test message\\\",\\\"recipient\\\":\\\"user@example.com\\\",\\\"type\\\":\\\"info\\\",\\\"metadata\\\":[]}\",\"headers\":{\"type\":\"App\\\\Message\\\\NotificationMessage\"}}'\n\n";

echo "# Check queue length\n";
echo "LLEN messenger_messages\n\n";

echo "# View messages in queue (without removing them)\n";
echo "LRANGE messenger_messages 0 -1\n\n";

echo "# Remove and process a message\n";
echo "RPOP messenger_messages\n\n";

// Example 3: Using Symfony Messenger in Code
echo "=== Example 3: Using Symfony Messenger in Code ===\n";
echo "<?php\n";
echo "use App\Message\NotificationMessage;\n";
echo "use Symfony\Component\Messenger\MessageBusInterface;\n\n";
echo "// In a controller or service\n";
echo "\$message = new NotificationMessage(\n";
echo "    content: 'Welcome to our platform!',\n";
echo "    recipient: 'user@example.com',\n";
echo "    type: 'info',\n";
echo "    metadata: ['source' => 'registration', 'priority' => 'normal']\n";
echo ");\n\n";
echo "\$this->messageBus->dispatch(\$message);\n\n";

// Example 4: Batch Processing
echo "=== Example 4: Batch Processing ===\n";
echo "<?php\n";
echo "// Process multiple messages at once\n";
echo "\$messages = [\n";
echo "    new NotificationMessage('Welcome!', 'user1@example.com'),\n";
echo "    new NotificationMessage('Account verified', 'user2@example.com'),\n";
echo "    new NotificationMessage('Password reset', 'user3@example.com'),\n";
echo "];\n\n";
echo "foreach (\$messages as \$message) {\n";
echo "    \$this->messageBus->dispatch(\$message);\n";
echo "}\n\n";

// Example 5: Monitoring and Debugging
echo "=== Example 5: Monitoring and Debugging ===\n";
echo "# Start the message consumer\n";
echo "php bin/console messenger:consume async -vv\n\n";

echo "# Check failed messages\n";
echo "php bin/console messenger:failed:show\n\n";

echo "# Retry failed messages\n";
echo "php bin/console messenger:failed:retry\n\n";

echo "# Check Redis connection\n";
echo "php bin/console debug:messenger\n\n";

// Example 6: Environment Variables
echo "=== Example 6: Environment Variables ===\n";
echo "# In .env file\n";
echo "REDIS_URL=redis://redis:6379\n";
echo "MESSENGER_TRANSPORT_DSN=redis://redis:6379\n\n";

// Example 7: Docker Commands
echo "=== Example 7: Docker Commands ===\n";
echo "# Start the application with Redis\n";
echo "docker-compose up -d\n\n";

echo "# Check Redis logs\n";
echo "docker logs apip-redis\n\n";

echo "# Connect to Redis from host\n";
echo "redis-cli -h localhost -p 6379\n\n";

echo "# Monitor Redis commands in real-time\n";
echo "docker exec -it apip-redis redis-cli monitor\n\n";

echo "=== Complete Example Commands ===\n";
echo "1. Send a simple notification:\n";
echo "   php bin/console app:send-notification user@example.com 'Hello World!'\n\n";

echo "2. Send a warning notification with metadata:\n";
echo "   php bin/console app:send-notification admin@example.com 'System maintenance in 1 hour' --type=warning --metadata='{\"priority\":\"high\",\"action\":\"schedule_maintenance\"}'\n\n";

echo "3. Start processing messages:\n";
echo "   php bin/console messenger:consume async -vv\n\n";

echo "4. Check Redis queue status:\n";
echo "   docker exec -it apip-redis redis-cli LLEN messenger_messages\n\n";

echo "5. View messages in queue:\n";
echo "   docker exec -it apip-redis redis-cli LRANGE messenger_messages 0 4\n\n";
