# Changelog - Al-Ghaya LMS

## [2025-10-13] - Email System Overhaul & OAuth2 Implementation

### ✨ New Features
- **OAuth2 Email Authentication**: Implemented secure Gmail OAuth2 for email sending
- **Automated OAuth2 Setup**: Added guided setup script for OAuth2 configuration
- **Forgot Password System**: Complete forgot password functionality with email verification
- **Email Templates**: Professional HTML email templates for password reset and welcome emails
- **Fallback SMTP Support**: Automatic fallback to SMTP if OAuth2 fails

### 🔧 Fixes
- **Fixed Gmail Authentication**: Resolved "535-5.7.8 Username and Password not accepted" errors
- **Fixed Missing Reset Links**: Proper reset link generation in forgot password emails
- **Fixed Function Conflicts**: Cleaned up conflicting `createMailer()` functions
- **Removed Debug Code**: Eliminated debug logging and demo mode remnants
- **Fixed Database Setup**: Automatic `password_resets` table creation

### 🔒 Security Improvements
- **Token-Based Reset**: Secure password reset tokens with 1-hour expiration
- **Email Enumeration Protection**: Generic success messages prevent user enumeration
- **One-Time Use Tokens**: Password reset tokens can only be used once
- **CSRF Protection**: Secure state validation for OAuth2 flows
- **Password Strength Validation**: Enhanced password requirements on reset

### 📝 Files Modified

#### Core Email System
- `php/mail-config.php` - Complete rewrite with OAuth2 support and SMTP fallback
- `pages/forgot-password.php` - Fixed reset link generation and email sending
- `php/oauth2-setup.php` - New OAuth2 setup wizard (to be deleted after setup)

#### Documentation
- `docs/EMAIL_SETUP.md` - Comprehensive setup guide for OAuth2 and SMTP
- `CHANGELOG.md` - This changelog documenting all changes

#### Database
- Auto-created `password_resets` table with proper indexes

### 🚀 Setup Instructions

#### For OAuth2 (Recommended)
1. Install dependencies: `composer require google/apiclient league/oauth2-google`
2. Configure Google Cloud Console (see docs/EMAIL_SETUP.md)
3. Run setup: Visit `http://localhost:8080/al-ghaya/php/oauth2-setup.php`
4. Add refresh token to `.env` file
5. Restart Apache
6. Delete setup file: `rm php/oauth2-setup.php`

#### For SMTP Fallback
1. Generate Gmail App Password
2. Add SMTP credentials to `.env` file
3. Restart Apache

### 📊 Environment Variables Required

```env
# OAuth2 Configuration (Recommended)
GMAIL_REFRESH_TOKEN=your-refresh-token
GMAIL_USER_EMAIL=your-email@gmail.com
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret

# SMTP Fallback Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Al-Ghaya LMS"
```

### ⚙️ Technical Improvements
- **Smart Mailer Selection**: Automatically chooses OAuth2 or SMTP based on configuration
- **Error Handling**: Comprehensive error logging and fallback mechanisms
- **Code Organization**: Separated OAuth2 email from Google login OAuth
- **Clean Architecture**: Modular functions for different email types
- **Professional Templates**: Mobile-responsive HTML email templates

### 📝 Migration Notes
- **No Breaking Changes**: Existing functionality preserved
- **Backward Compatibility**: SMTP still works as fallback
- **Database Changes**: `password_resets` table created automatically
- **Configuration**: New environment variables required for OAuth2

### 🔍 Testing
- Forgot password flow works with both OAuth2 and SMTP
- Email templates render correctly across email clients
- Token expiration and security features validated
- Error handling and fallback mechanisms tested
- Google OAuth login (separate from email) continues to work

### 🚨 Security Notes
- Delete `php/oauth2-setup.php` after initial setup
- OAuth2 refresh tokens should be kept secure
- SMTP passwords should use App Passwords, not account passwords
- Monitor email sending quotas and API usage

### 📚 Documentation
- Complete setup guide in `docs/EMAIL_SETUP.md`
- Troubleshooting section with common issues
- Security checklist for production deployment
- API quota information and limitations

---

## Previous Versions

### [2025-10-13] - Initial OAuth & Forgot Password Implementation
- Basic forgot password functionality
- Google OAuth login for user authentication
- Initial email configuration attempts
- Database structure for user management

---

**Note**: This changelog documents major system improvements. For detailed commit history, see the Git log.