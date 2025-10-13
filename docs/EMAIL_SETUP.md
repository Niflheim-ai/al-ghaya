# Email Setup Guide - Al-Ghaya LMS

This guide will help you set up email functionality for the Al-Ghaya Learning Management System, including forgot password emails and teacher welcome emails.

## Overview

The system supports two email authentication methods:
1. **OAuth2 (Recommended)** - Uses Google OAuth2 for secure, token-based authentication
2. **SMTP Fallback** - Traditional username/password authentication

## Prerequisites

1. **PHP Composer** - Required for OAuth2 dependencies
2. **Gmail Account** - For OAuth2 setup
3. **Google Cloud Console Access** - For OAuth2 configuration

## Method 1: OAuth2 Setup (Recommended)

### Step 1: Install Required Dependencies

Run this command in your project root:
```bash
composer require google/apiclient:"^2.15.0" league/oauth2-google
```

### Step 2: Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your existing project or create a new one
3. Enable the **Gmail API**:
   - Navigate to "APIs & Services" → "Library"
   - Search for "Gmail API"
   - Click "Enable"

4. Create OAuth2 Credentials:
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth client ID"
   - Choose "Web application"
   - Add these redirect URIs:
     - `http://localhost:8080/al-ghaya/php/oauth2-setup.php`
     - `https://your-tunnel-domain.loca.lt/al-ghaya/php/oauth2-setup.php` (if using localtunnel)

5. Download the credentials or note the Client ID and Client Secret

### Step 3: Configure Environment Variables

Add these to your `.env` file:
```env
# Gmail OAuth2 Configuration
GMAIL_REFRESH_TOKEN=
GMAIL_USER_EMAIL=your-email@gmail.com

# Google OAuth (should already exist)
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

### Step 4: Run OAuth2 Setup

1. **Visit the setup URL**: `http://localhost:8080/al-ghaya/php/oauth2-setup.php`
2. **Click "Authorize Gmail Access"**
3. **Sign in to your Gmail account**
4. **Grant permissions** for email sending
5. **Copy the generated refresh token** to your `.env` file
6. **Restart Apache** to reload environment variables
7. **Delete the setup file** for security: `rm php/oauth2-setup.php`

### Step 5: Test Email Functionality

1. Visit the forgot password page: `http://localhost:8080/al-ghaya/pages/forgot-password.php`
2. Enter a valid email address from your user table
3. Check that the email is sent successfully

## Method 2: SMTP Fallback Setup

If OAuth2 setup fails, the system will automatically fall back to SMTP authentication.

### Gmail SMTP Configuration

Add these to your `.env` file:
```env
# SMTP Configuration (Fallback)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Al-Ghaya LMS"
```

**Important:** You need to:
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password (not your regular password)
3. Use the 16-character App Password in `MAIL_PASSWORD`

### Alternative SMTP Providers

#### Outlook/Hotmail
```env
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

#### SendGrid (Production)
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

#### Mailtrap (Testing)
```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

## Database Setup

The system will automatically create the `password_resets` table when needed. If you want to create it manually:

```sql
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);
```

## Features

### Forgot Password Flow
1. User enters email on forgot password page
2. System generates secure token (expires in 1 hour)
3. Email sent with reset link
4. User clicks link and sets new password
5. Token is marked as used (one-time use)

### Security Features
- **Email enumeration protection** - Generic success messages
- **Token expiration** - Links expire in 1 hour
- **One-time use tokens** - Prevents replay attacks
- **CSRF protection** - Secure token generation
- **Password strength validation** - On reset page

### Email Templates
- **Professional HTML design**
- **Mobile-responsive**
- **Branded with Al-Ghaya LMS styling**
- **Security notices and warnings**

## Troubleshooting

### Common Issues

1. **"Gmail refresh token not found"**
   - Run the OAuth2 setup script again
   - Make sure the token is in your `.env` file
   - Restart Apache after updating `.env`

2. **"SMTP authentication failed"**
   - Check your Gmail App Password
   - Ensure 2FA is enabled on Gmail
   - Verify MAIL_USERNAME and MAIL_PASSWORD in `.env`

3. **"Access token refresh failed"**
   - Your OAuth2 refresh token may be expired
   - Re-run the OAuth2 setup to get a new token

4. **Emails not being received**
   - Check spam/junk folders
   - Verify recipient email exists in user table
   - Check server error logs for email sending errors

### Debug Mode

Enable debug mode in `.env` to see detailed error messages:
```env
APP_DEBUG=true
```

### Log Files

Check these for email-related errors:
- PHP error log (location varies by system)
- Apache error log
- Application-specific error logs

## Production Deployment

### Security Checklist
- [ ] Delete `php/oauth2-setup.php` after setup
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use HTTPS for all OAuth2 redirects
- [ ] Regularly rotate OAuth2 tokens
- [ ] Monitor email sending quotas
- [ ] Set up proper error monitoring

### Gmail API Quotas

Gmail API has sending limits:
- **Free tier**: 250 emails per day
- **Google Workspace**: Higher limits available
- Consider using dedicated email services for high volume

## Support

For issues with email setup:
1. Check the troubleshooting section above
2. Review server error logs
3. Test with different email providers
4. Verify OAuth2 credentials in Google Cloud Console

## Files Modified

- `php/mail-config.php` - Email configuration and OAuth2 setup
- `pages/forgot-password.php` - Forgot password form and processing
- `pages/reset-password.php` - Password reset form
- `php/oauth2-setup.php` - OAuth2 setup script (delete after use)
- `php/authorized.php` - Google OAuth login (separate from email)
