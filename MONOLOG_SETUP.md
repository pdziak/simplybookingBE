# Monolog Logging Setup

This document explains the Monolog logging configuration for the Benefitowo backend application.

## Overview

Monolog is now fully integrated and configured to provide structured logging for your application, with special attention to subdomain creation and management.

## Installation

Monolog is installed via Composer:

```bash
composer require symfony/monolog-bundle
```

## Configuration

### Log Channels

Three custom log channels are configured:

1. **deprecation** - For PHP and Symfony deprecation warnings
2. **subdomain** - Dedicated channel for subdomain creation and management
3. **app** - General application-related logs

### Log Handlers

#### Development Environment (`var/log/dev.log`)

- **main handler**: Logs all messages except events and subdomain logs
- **subdomain handler**: Dedicated log file at `var/log/subdomain.log`
- **console handler**: Outputs logs to console during CLI commands

#### Production Environment (`php://stderr`)

- **main handler**: Uses "fingers crossed" strategy (only logs when errors occur)
- **subdomain handler**: Always logs subdomain operations with JSON formatting
- **console handler**: Outputs to console for CLI
- **deprecation handler**: Separate stream for deprecation warnings

## Usage in AppController

The `AppController` is configured to use the subdomain logger:

```php
use Psr\Log\LoggerInterface;

class AppController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger  // Automatically injects subdomain logger
    ) {}
    
    private function executeSendNotificationCommand(string $subdomain): void
    {
        $this->logger->info('Starting subdomain creation command', [
            'subdomain' => $subdomain,
            'action' => 'app:send-notification'
        ]);
        
        // ... command execution ...
        
        if ($process->isSuccessful()) {
            $this->logger->info('SendNotificationCommand executed successfully', [
                'subdomain' => $subdomain,
                'output' => $process->getOutput()
            ]);
        } else {
            $this->logger->error('SendNotificationCommand failed', [
                'subdomain' => $subdomain,
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode()
            ]);
        }
    }
}
```

## Log Levels

Monolog supports these log levels (from least to most severe):

- **DEBUG**: Detailed debug information
- **INFO**: Interesting events (e.g., subdomain creation started)
- **NOTICE**: Normal but significant events
- **WARNING**: Exceptional occurrences that are not errors
- **ERROR**: Runtime errors that don't require immediate action
- **CRITICAL**: Critical conditions
- **ALERT**: Action must be taken immediately
- **EMERGENCY**: System is unusable

## Log Files Location

### Development
- Main logs: `var/log/dev.log`
- Subdomain logs: `var/log/subdomain.log`

### Production
- All logs: `php://stderr` (standard error output)
- Format: JSON for easy parsing by log aggregation tools

## Viewing Logs

### Development
```bash
# View all logs
tail -f var/log/dev.log

# View subdomain logs only
tail -f var/log/subdomain.log

# Search for specific subdomain
grep "test-shop" var/log/subdomain.log
```

### Production
```bash
# Logs are typically collected by your hosting provider's log management system
# Check your hosting provider's documentation for log access
```

## Structured Logging

All subdomain-related logs include structured data:

```json
{
  "message": "SendNotificationCommand executed successfully",
  "context": {
    "subdomain": "test-shop",
    "output": "Command output here",
    "timestamp": "2025-10-19T12:34:56+00:00"
  },
  "level": "INFO",
  "channel": "subdomain"
}
```

## Benefits

1. **Dedicated subdomain logs** - Easy to track subdomain creation and issues
2. **Structured logging** - Context data makes debugging easier
3. **Production-ready** - JSON format works with log aggregation tools
4. **Performance** - "Fingers crossed" handler minimizes overhead in production
5. **Separation of concerns** - Different channels for different types of logs

## Integration with External Services

The JSON format makes it easy to integrate with:

- **ELK Stack** (Elasticsearch, Logstash, Kibana)
- **Splunk**
- **Datadog**
- **CloudWatch** (AWS)
- **Stackdriver** (Google Cloud)

## Troubleshooting

### Logs not appearing

1. Check file permissions:
```bash
chmod -R 777 var/log/
```

2. Clear cache:
```bash
php bin/console cache:clear
```

3. Check Monolog configuration:
```bash
php bin/console debug:config monolog
```

### Too many logs

Adjust the log level in `config/packages/monolog.yaml`:

```yaml
subdomain:
    type: stream
    path: "%kernel.logs_dir%/subdomain.log"
    level: warning  # Only log warnings and above
    channels: ["subdomain"]
```

## Best Practices

1. **Use appropriate log levels** - Don't log everything as ERROR
2. **Include context** - Always add relevant data to log messages
3. **Don't log sensitive data** - Avoid passwords, API keys, etc.
4. **Rotate logs** - Set up log rotation to prevent disk space issues
5. **Monitor logs** - Set up alerts for ERROR and CRITICAL level logs

## Example: Testing the Logger

Create a test app via your API and check the logs:

```bash
# Create a new app
curl -X POST http://localhost:8000/api/apps \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Shop","slug":"test-shop","companyName":"Test","email":"test@example.com"}'

# Check subdomain logs
cat var/log/subdomain.log
```

You should see entries like:
```
[2025-10-19T12:34:56.123456+00:00] subdomain.INFO: Starting subdomain creation command {"subdomain":"test-shop","action":"app:send-notification"}
[2025-10-19T12:34:57.123456+00:00] subdomain.INFO: SendNotificationCommand executed successfully {"subdomain":"test-shop","output":"..."}
```

## Further Reading

- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [Symfony Logging](https://symfony.com/doc/current/logging.html)
- [Monolog Handlers](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md)


