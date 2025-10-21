# Production Email Fix - Registration Emails Not Sending

## 🚨 Problem Identified
The current `MAILER_DSN=smtp://localhost:1025` configuration only works in local development with MailHog. In production, this mail server doesn't exist, so emails fail silently.

## ✅ Quick Fix Solutions

### Option 1: Gmail SMTP (Fastest Setup)

Update your production `.env` file:

```env
# Replace the existing MAILER_DSN line with:
MAILER_DSN=smtp://your-email@gmail.com:your-app-password@smtp.gmail.com:587

# Keep these as they are:
MAILER_ADMIN_EMAIL=kontakt@simplybooking.pl
MAILER_ADMIN_NAME=Benefitowo Team
APP_URL=https://your-domain.com
```

**Gmail Setup Steps:**
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Factor Authentication
3. Generate an App Password:
   - Go to "App passwords"
   - Select "Mail" and "Other (custom name)"
   - Enter "SimplyBooking" as the name
   - Copy the generated 16-character password
4. Use: `smtp://your-email@gmail.com:generated-app-password@smtp.gmail.com:587`

### Option 2: SendGrid (Recommended for Production)

```env
MAILER_DSN=smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587
MAILER_ADMIN_EMAIL=kontakt@simplybooking.pl
MAILER_ADMIN_NAME=Benefitowo Team
APP_URL=https://your-domain.com
```

**SendGrid Setup:**
1. Sign up at [sendgrid.com](https://sendgrid.com)
2. Create an API key in Settings > API Keys
3. Verify your sender email address
4. Use the API key in the DSN above

### Option 3: Mailtrap (For Testing)

```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
MAILER_ADMIN_EMAIL=kontakt@simplybooking.pl
MAILER_ADMIN_NAME=Benefitowo Team
APP_URL=https://your-domain.com
```

## 🔧 Testing the Fix

### 1. Test Email Endpoint
I've added a test endpoint to verify email configuration:

```bash
# Test email sending
curl -X POST https://your-domain.com/api/test-email \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'
```

### 2. Check Application Logs
```bash
# Check for email errors
tail -f var/log/prod.log | grep -i "email\|mail\|smtp"

# Or check all logs
tail -f var/log/prod.log
```

### 3. Test Registration Flow
1. Try registering a new user
2. Check logs for email sending attempts
3. Verify the user receives the verification email

## 🐛 Debugging Steps

### Step 1: Check Current Configuration
```bash
# Check if mailer is configured
php bin/console debug:container mailer

# Check environment variables
php bin/console debug:container --parameter=mailer.dsn
```

### Step 2: Test Email Service
```bash
# Test the email service directly
php bin/console app:test-email test@example.com
```

### Step 3: Check Database
Verify that verification tokens are being created:
```sql
SELECT id, email, email_verification_token, email_verification_token_expires_at 
FROM users 
WHERE email_verification_token IS NOT NULL 
ORDER BY created_at DESC 
LIMIT 5;
```

## 🚀 Implementation Steps

1. **Choose an email service** (Gmail for quick setup)
2. **Update production `.env`** with correct `MAILER_DSN`
3. **Clear and rebuild cache:**
   ```bash
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```
4. **Test email sending** using the test endpoint
5. **Test user registration** to verify the full flow

## 📋 Common Issues

### Issue: "Connection refused"
- **Cause:** Wrong SMTP server/port
- **Fix:** Verify SMTP settings and port (587 for TLS, 465 for SSL)

### Issue: "Authentication failed"
- **Cause:** Wrong username/password
- **Fix:** For Gmail, use App Password, not regular password

### Issue: "TLS/SSL error"
- **Cause:** Wrong encryption settings
- **Fix:** Use port 587 for TLS or 465 for SSL

### Issue: Emails sent but not received
- **Cause:** Spam folder or email provider blocking
- **Fix:** Check spam folder, verify sender domain

## 🔒 Security Notes

- Never commit email credentials to version control
- Use environment variables for all sensitive data
- Rotate API keys regularly
- Monitor email usage for unusual activity

## 📞 Support

If you continue having issues:
1. Check the application logs for specific error messages
2. Test with a simple email service first (Gmail)
3. Verify your domain's email reputation
4. Consider using a professional email service (SendGrid, Mailgun)
