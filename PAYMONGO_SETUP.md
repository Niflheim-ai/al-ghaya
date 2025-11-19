# PayMongo Integration Setup Guide

## ❌ Common Error: HTTP 403 CloudFront Error

If you're seeing this error in your logs:
```
PayMongo API Error (HTTP 403): <!DOCTYPE HTML...CloudFront...
```

This means **CloudFront (PayMongo's CDN) is blocking your API requests**.

---

## 🔧 Quick Fix Steps

### Step 1: Verify Your API Keys

1. **Log in to PayMongo Dashboard**
   - Go to: https://dashboard.paymongo.com/
   - Navigate to **Developers** → **API Keys**

2. **Check Account Status**
   - Ensure your account is **verified** and **activated**
   - Test mode should be enabled if you're in development

3. **Generate New API Keys**
   - If your keys are old or potentially compromised, generate new ones
   - Copy both the **Secret Key** and **Public Key**

### Step 2: Update Your `.env` File

Make sure your `.env` file (in the root of `al-ghaya` folder) contains:

```env
# PayMongo API Configuration
PAYMONGO_SECRET_KEY=sk_test_your_actual_secret_key_here
PAYMONGO_PUBLIC_KEY=pk_test_your_actual_public_key_here
PAYMONGO_TEST_MODE=true

# App Configuration
APP_URL=http://localhost/al-ghaya
APP_DEBUG=true
```

**Important Notes:**
- ✅ For **testing/development**, use keys that start with `sk_test_` and `pk_test_`
- ⚠️ For **production**, use keys that start with `sk_live_` and `pk_live_`
- ❌ **Never commit** your `.env` file to Git (it should be in `.gitignore`)

### Step 3: Test Your Connection

1. Navigate to the test script:
   ```
   http://localhost/al-ghaya/php/test-paymongo.php
   ```

2. Review the output:
   - ✅ **All tests pass** = Your integration is working!
   - ❌ **403 error** = Follow the additional troubleshooting steps below

### Step 4: Clear Errors and Test Again

After updating your API keys:

1. Restart your XAMPP Apache server
2. Clear your browser cache
3. Try creating a payment again
4. Check your Apache error log for any new errors

---

## 🔍 Root Causes of 403 CloudFront Error

### 1. ❌ Using Test Keys in Wrong Environment
- **Problem**: Using `sk_test_` keys when PayMongo expects live keys (or vice versa)
- **Solution**: Match your key type to your environment setting

### 2. ❌ Invalid or Expired API Keys
- **Problem**: Keys were regenerated, revoked, or copied incorrectly
- **Solution**: Generate fresh API keys from the dashboard

### 3. ❌ Account Not Verified
- **Problem**: Your PayMongo account needs verification before processing payments
- **Solution**: Complete account verification at https://dashboard.paymongo.com/

### 4. ❌ Missing User-Agent Header
- **Problem**: CloudFront requires a User-Agent header (already fixed in latest code)
- **Solution**: Update to latest `paymongo-helper.php` (already done)

### 5. ❌ API Restrictions
- **Problem**: IP address or domain restrictions set on your PayMongo account
- **Solution**: Check API restrictions in PayMongo dashboard settings

---

## 🛠️ What I Fixed in Your Code

### Updated Files:
1. **`php/paymongo-helper.php`**
   - ✅ Added `User-Agent` header to all API requests
   - ✅ Improved SSL certificate verification
   - ✅ Added request timeouts (30 seconds)
   - ✅ Enabled redirect following for CloudFront redirects
   - ✅ Enhanced error logging with detailed 403 error messages
   - ✅ Better error handling and response parsing

2. **`php/test-paymongo.php`** (NEW)
   - ✅ Comprehensive connection testing
   - ✅ API key validation
   - ✅ Detailed error diagnostics
   - ✅ Step-by-step troubleshooting guidance

---

## 📝 Testing Checklist

Before testing payments:

- [ ] PayMongo account is verified and activated
- [ ] API keys are correctly copied to `.env` file
- [ ] Using **test keys** (`sk_test_` / `pk_test_`) for development
- [ ] `PAYMONGO_TEST_MODE=true` in `.env`
- [ ] Test script (`test-paymongo.php`) passes all tests
- [ ] Apache server restarted after `.env` changes
- [ ] No 403 errors in Apache error log

---

## 📞 Need Help?

### PayMongo Support
- Dashboard: https://dashboard.paymongo.com/
- Documentation: https://developers.paymongo.com/docs
- Support: support@paymongo.com

### Common Test Scenarios

#### Test Payment in Development:
1. Set `PAYMONGO_TEST_MODE=true`
2. Use test API keys (`sk_test_...`)
3. Use test payment methods:
   - **GCash Test**: Use any valid mobile number
   - **Card Test**: `4120 0000 0000 0007` (success)
   - **Card Test**: `4120 0000 0000 0015` (failure)

#### Go Live in Production:
1. Complete PayMongo account verification
2. Set `PAYMONGO_TEST_MODE=false`
3. Use live API keys (`sk_live_...`)
4. Update `APP_URL` to your production domain
5. Test with small real payment first

---

## 📦 Database Schema

Your PayMongo integration uses this table:

```sql
CREATE TABLE payment_transactions (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PHP',
    payment_provider VARCHAR(50) DEFAULT 'paymongo',
    payment_method VARCHAR(50),
    payment_session_id VARCHAR(255),
    payment_source_id VARCHAR(255),
    status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    payment_details TEXT,
    dateCreated DATETIME DEFAULT CURRENT_TIMESTAMP,
    datePaid DATETIME,
    FOREIGN KEY (student_id) REFERENCES user(userID),
    FOREIGN KEY (program_id) REFERENCES programs(programID),
    INDEX idx_session (payment_session_id),
    INDEX idx_source (payment_source_id),
    INDEX idx_student_program (student_id, program_id)
);
```

---

## ✅ Success Indicators

You'll know PayMongo is working when:

1. **No 403 errors** in Apache error log
2. **Test script passes** all connection tests
3. **Checkout URL generated** when enrolling in paid program
4. **Payment page loads** at PayMongo's hosted checkout
5. **Payment status** updates in database after completion
6. **Student enrollment** created upon successful payment

---

## 🚨 Security Reminders

⚠️ **NEVER:**
- Commit `.env` file to Git
- Share your secret API keys publicly
- Use live keys in development environment
- Store API keys in JavaScript/frontend code

✅ **ALWAYS:**
- Use test keys for development
- Keep `.env` file in `.gitignore`
- Regenerate keys if compromised
- Use environment variables for sensitive data
- Enable HTTPS in production

---

## 📋 Changelog

### 2025-11-19
- Fixed CloudFront 403 error by adding User-Agent header
- Improved cURL request configuration
- Added comprehensive error logging
- Created test script for connection validation
- Added this setup guide

---

Good luck! 🚀