# 🔧 Gmail OAuth2 & SMTP Setup Guide for Al-Ghaya LMS

This guide will help you set up fresh Gmail credentials for sending emails from your Al-Ghaya LMS application using both OAuth2 (recommended) and SMTP methods.

## 📋 Prerequisites

- Gmail account with 2-factor authentication enabled
- Google Cloud Console access
- XAMPP running with Al-Ghaya LMS installed
- Composer dependencies installed

---

## 🚀 Method 1: OAuth2 Setup (Recommended)

OAuth2 is more secure and doesn't require app passwords.

### Step 1: Google Cloud Console Setup

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/
   - Sign in with your Gmail account

2. **Create or Select a Project**
   ```
   Project Name: Al-Ghaya-LMS
   Project ID: al-ghaya-lms-[random-id]
   ```

3. **Enable Gmail API**
   - Go to "APIs & Services" → "Library"
   - Search for "Gmail API"
   - Click "Enable"

4. **Configure OAuth Consent Screen**
   - Go to "APIs & Services" → "OAuth consent screen"
   - Choose "External" (unless you have Google Workspace)
   - Fill in required fields:
     ```
     App name: Al-Ghaya LMS
     User support email: [your-gmail]
     Developer contact email: [your-gmail]
     ```
   - Add scopes:
     - `https://www.googleapis.com/auth/gmail.send`
     - `https://www.googleapis.com/auth/userinfo.email`
   - Add test users (your Gmail account)
   - Save and continue

5. **Create OAuth2 Credentials**
   - Go to "APIs & Services" → "Credentials"
   - Click "+ CREATE CREDENTIALS" → "OAuth client ID"
   - Choose "Web application"
   - Configure:
     ```
     Name: Al-Ghaya OAuth Client
     Authorized JavaScript origins:
       - http://localhost:8080
       - http://localhost
     Authorized redirect URIs:
       - http://localhost:8080/al-ghaya/php/oauth2-setup.php
       - http://localhost/al-ghaya/php/oauth2-setup.php
     ```
   - Click "Create"
   - **Download the JSON file** or copy the Client ID and Client Secret

### Step 2: Configure Environment Variables

1. **Create `.env` file** in your al-ghaya root directory:
   ```env
   # App Configuration
   APP_NAME="Al-Ghaya LMS"
   APP_ENV=development
   APP_DEBUG=true
   APP_URL=http://localhost:8080/al-ghaya
   
   # Database Configuration
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=al_ghaya_lms
   DB_USERNAME=root
   DB_PASSWORD=
   
   # Google OAuth2 Configuration
   GOOGLE_CLIENT_ID=your-client-id-here.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your-client-secret-here
   
   # Gmail Configuration (will be filled after OAuth2 setup)
   GMAIL_REFRESH_TOKEN=
   GMAIL_USER_EMAIL=your-gmail@gmail.com
   
   # Email From Configuration
   MAIL_FROM_ADDRESS=your-gmail@gmail.com
   MAIL_FROM_NAME="Al-Ghaya LMS"
   ```

2. **Replace the credentials:**
   - Replace `your-client-id-here` with your actual Client ID
   - Replace `your-client-secret-here` with your actual Client Secret
   - Replace `your-gmail@gmail.com` with your Gmail address

### Step 3: Generate OAuth2 Refresh Token

1. **Install Composer Dependencies** (if not done):
   ```bash
   cd /path/to/al-ghaya
   composer install
   ```

2. **Run OAuth2 Setup**:
   - Open: http://localhost:8080/al-ghaya/php/oauth2-setup.php
   - Click "Authorize Gmail Access"
   - Sign in to your Gmail account
   - Grant permissions for:
     - Send emails on your behalf
     - View your email address
   - Copy the generated `GMAIL_REFRESH_TOKEN` to your `.env` file

3. **Update `.env` file** with the refresh token:
   ```env
   GMAIL_REFRESH_TOKEN=1//your-long-refresh-token-here
   ```

4. **Delete setup file for security**:
   ```bash
   rm php/oauth2-setup.php
   ```

---

## 📧 Method 2: SMTP with App Password (Alternative)

If OAuth2 doesn't work for your environment, use SMTP with Gmail App Password.

### Step 1: Enable 2-Factor Authentication

1. Go to: https://myaccount.google.com/security
2. Enable "2-Step Verification"

### Step 2: Generate App Password

1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail" and "Other (custom name)"
3. Enter: "Al-Ghaya LMS"
4. Copy the 16-character password

### Step 3: Configure SMTP Settings

Add to your `.env` file:
```env
# SMTP Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="Al-Ghaya LMS"
```

---

## 🧪 Testing Your Setup

### Test OAuth2 Configuration

1. **Test Password Reset Email:**
   - Go to: http://localhost:8080/al-ghaya/pages/login.php
   - Click "Forgot Password"
   - Enter a test email address
   - Check if email is sent successfully

2. **Test Teacher Welcome Email:**
   - Login as admin
   - Create a new teacher account
   - Check if welcome email is sent

### Debug Configuration

Create `test-email.php` in your php folder:
```php
<?php
require_once 'mail-config.php';

try {
    $result = sendPasswordResetEmail(
        'test@example.com', 
        'Test User', 
        'http://localhost:8080/reset-test'
    );
    
    if ($result['success']) {
        echo "✅ Email sent successfully!";
    } else {
        echo "❌ Failed: " . $result['error'];
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Run: http://localhost:8080/al-ghaya/php/test-email.php

---

## 🔒 Security Best Practices

1. **Environment File Security:**
   ```bash
   # Add to .gitignore
   echo ".env" >> .gitignore
   
   # Set proper permissions
   chmod 600 .env
   ```

2. **OAuth2 Scopes:**
   - Only request necessary scopes
   - Use `gmail.send` (not `gmail.modify` or `gmail.readonly`)

3. **Token Storage:**
   - Refresh tokens never expire unless revoked
   - Store securely in environment variables
   - Never commit to version control

4. **App Passwords:**
   - Use unique passwords for each application
   - Revoke unused passwords regularly
   - Never share or commit passwords

---

## 🐛 Troubleshooting

### Common OAuth2 Issues

**Error: "redirect_uri_mismatch"**
- Solution: Check redirect URIs in Google Cloud Console match exactly
- Include both `http://localhost` and `http://localhost:8080` variants

**Error: "invalid_grant"**
- Solution: Regenerate refresh token using oauth2-setup.php
- Check system clock is synchronized

**Error: "insufficient_scope"**
- Solution: Ensure Gmail API is enabled and correct scopes are requested

### Common SMTP Issues

**Error: "Authentication failed"**
- Solution: 
  1. Verify 2FA is enabled
  2. Generate new app password
  3. Check username/password in .env

**Error: "Connection refused"**
- Solution:
  1. Check port (587 for TLS, 465 for SSL)
  2. Verify firewall settings
  3. Test with telnet: `telnet smtp.gmail.com 587`

### Debug Steps

1. **Check PHP Extensions:**
   ```bash
   php -m | grep -E "(openssl|curl|json)"
   ```

2. **Verify .env Loading:**
   ```php
   var_dump(Config::get('GOOGLE_CLIENT_ID'));
   ```

3. **Enable Debug Logging:**
   ```php
   // Add to mail-config.php
   $mail->SMTPDebug = 2;
   ```

---

## 📁 File Structure

After setup, your structure should look like:
```
al-ghaya/
├── .env                    # Your credentials (DO NOT COMMIT)
├── .gitignore             # Should include .env
├── php/
│   ├── config.php         # Configuration loader
│   ├── mail-config.php    # Email configuration
│   ├── oauth2-setup.php   # OAuth2 setup (delete after use)
│   └── test-email.php     # Testing script (optional)
└── vendor/                # Composer dependencies
```

---

## 🎯 Final Checklist

- [ ] Google Cloud Console project created
- [ ] Gmail API enabled
- [ ] OAuth consent screen configured
- [ ] OAuth2 credentials generated
- [ ] `.env` file created with credentials
- [ ] Refresh token generated
- [ ] Email sending tested
- [ ] Setup files removed for security
- [ ] `.env` added to `.gitignore`

---

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Verify all credentials in `.env` file
3. Test with the provided debug scripts
4. Check server logs for detailed error messages

Remember: OAuth2 is recommended for production environments due to better security and reliability.