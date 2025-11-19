# Payment Providers Setup Guide

## 🎯 Overview

Al-Ghaya LMS now supports **multiple payment providers** with **automatic fallback**:

1. **PayMongo** - Cards, GCash, PayMaya, GrabPay, QRPh
2. **Xendit** ⭐ **RECOMMENDED** - GCash, PayMaya, GrabPay, ShopeePay, QR Codes, Cards
3. **Manual Bank Transfer** - Always available as last resort

**How it works**: If PayMongo fails, the system automatically falls back to Xendit. If Xendit fails, it falls back to manual bank transfer.

---

## ⭐ RECOMMENDED: Xendit Setup

### Why Xendit?

✅ **More reliable** than PayMongo for Filipino payment methods  
✅ **Better support** for GCash, PayMaya, and e-wallets  
✅ **Easier account verification**  
✅ **More flexible** API with better error handling  
✅ **Lower failure rate** on transactions

### Step 1: Create Xendit Account

1. Go to: **https://dashboard.xendit.co/register**
2. Sign up with your email
3. Complete business verification (simpler than PayMongo)
4. Activate your account

### Step 2: Get API Keys

1. Log in to **https://dashboard.xendit.co/**
2. Go to **Settings** → **Developers** → **API Keys**
3. You'll see two keys:
   - **Secret Key** (starts with `xnd_development_` for test mode)
   - **Public Key** (starts with `xnd_public_development_` for test mode)
4. Copy both keys

### Step 3: Configure in .env

Add to your `.env` file:

```env
PAYMENT_PROVIDER=xendit

XENDIT_SECRET_KEY=xnd_development_YOUR_SECRET_KEY_HERE
XENDIT_PUBLIC_KEY=xnd_public_development_YOUR_PUBLIC_KEY_HERE
XENDIT_TEST_MODE=true
```

### Step 4: Test

1. Restart Apache
2. Try enrolling in a paid program
3. You'll see payment options:
   - GCash
   - PayMaya  
   - GrabPay
   - ShopeePay
   - QR Code (InstaPay/PESONet)
   - Credit/Debit Cards

### Test Payment Credentials (Xendit)

**GCash Test:**
- Use any valid phone number
- Click "Success" on the test page

**PayMaya Test:**
- Use any valid phone number
- Click "Success" on the test page

**Card Test:**
- Card: `4000 0000 0000 0002`
- Expiry: Any future date
- CVV: `123`

---

## 🔄 Alternative: PayMongo Setup

### When to Use PayMongo?

- If you already have an activated PayMongo account
- If Xendit is not available in your region
- As a backup option

### Setup Instructions

See **[PAYMONGO_SETUP.md](PAYMONGO_SETUP.md)** for detailed PayMongo configuration.

**Quick setup:**

```env
PAYMENT_PROVIDER=paymongo

PAYMONGO_SECRET_KEY=sk_test_YOUR_SECRET_KEY_HERE
PAYMONGO_PUBLIC_KEY=pk_test_YOUR_PUBLIC_KEY_HERE
PAYMONGO_TEST_MODE=true
```

---

## 💰 Manual Bank Transfer Setup

### When to Use Manual Payment?

- **Always available** as last resort
- Students pay via bank transfer
- You manually verify payments
- No payment gateway fees

### Configuration

Add to your `.env` file:

```env
BANK_NAME="BDO Unibank"
BANK_ACCOUNT_NAME="Your School Name"
BANK_ACCOUNT_NUMBER="1234-5678-9012"
BANK_TRANSFER_INSTRUCTIONS="Please transfer the enrollment fee and upload proof of payment. We'll verify within 24 hours."
```

### How It Works

1. Student selects program enrollment
2. If online payments fail, they see bank transfer details
3. Student transfers money to your bank account
4. Student uploads proof of payment (screenshot/photo)
5. Admin verifies payment in dashboard
6. Admin approves enrollment

---

## 🔄 Automatic Fallback System

### How It Works

The system automatically tries payment providers in this order:

```
1. Preferred Provider (set in .env)
   ↓ (if fails)
2. PayMongo
   ↓ (if fails)
3. Xendit
   ↓ (if fails)
4. Manual Bank Transfer
```

### Example Scenarios

**Scenario 1: PayMongo configured but having issues**
```env
PAYMENT_PROVIDER=paymongo
PAYMONGO_SECRET_KEY=sk_test_...
XENDIT_SECRET_KEY=xnd_development_...
```

Result: System tries PayMongo first. If 403 error occurs, automatically switches to Xendit.

**Scenario 2: Only manual payment configured**
```env
PAYMENT_PROVIDER=manual
BANK_NAME="BDO"
```

Result: All enrollments go through manual bank transfer.

**Scenario 3: Multiple providers configured**
```env
PAYMENT_PROVIDER=xendit
XENDIT_SECRET_KEY=xnd_development_...
PAYMONGO_SECRET_KEY=sk_test_...
BANK_NAME="BDO"
```

Result: Uses Xendit by default. Falls back to PayMongo if Xendit fails. Falls back to manual if both fail.

---

## 🎯 Recommended Configuration

### For Development/Testing

```env
PAYMENT_PROVIDER=xendit

# Xendit (Primary)
XENDIT_SECRET_KEY=xnd_development_YOUR_KEY
XENDIT_PUBLIC_KEY=xnd_public_development_YOUR_KEY
XENDIT_TEST_MODE=true

# Manual (Fallback)
BANK_NAME="BDO Unibank"
BANK_ACCOUNT_NAME="Al-Ghaya LMS"
BANK_ACCOUNT_NUMBER="Test-Account"
```

### For Production

```env
PAYMENT_PROVIDER=xendit

# Xendit (Primary)
XENDIT_SECRET_KEY=xnd_production_YOUR_LIVE_KEY
XENDIT_PUBLIC_KEY=xnd_public_production_YOUR_LIVE_KEY
XENDIT_TEST_MODE=false

# PayMongo (Backup)
PAYMONGO_SECRET_KEY=sk_live_YOUR_LIVE_KEY
PAYMONGO_PUBLIC_KEY=pk_live_YOUR_LIVE_KEY
PAYMONGO_TEST_MODE=false

# Manual (Last Resort)
BANK_NAME="BDO Unibank"
BANK_ACCOUNT_NAME="Your Actual Account Name"
BANK_ACCOUNT_NUMBER="Your-Real-Account-Number"
BANK_TRANSFER_INSTRUCTIONS="Transfer to the account above and upload proof. Verification within 24 hours."
```

---

## 📊 Comparison Table

| Feature | Xendit ⭐ | PayMongo | Manual |
|---------|----------|----------|--------|
| **Setup Difficulty** | Easy | Medium | Easiest |
| **Account Verification** | 1-2 days | 3-7 days | N/A |
| **GCash Support** | ✅ Excellent | ✅ Good | ❌ |
| **PayMaya Support** | ✅ Excellent | ✅ Good | ❌ |
| **QR Code Support** | ✅ Yes | ✅ Yes | ❌ |
| **Transaction Fees** | 2.5-3.5% | 3.5-4% | Free |
| **API Reliability** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | N/A |
| **Processing Time** | Instant | Instant | 1-24 hours |
| **Admin Effort** | Low | Low | High |

---

## ✅ Testing Checklist

### Before Going Live

- [ ] Xendit account created and verified
- [ ] Test mode API keys working
- [ ] Successfully completed test payment (GCash/PayMaya)
- [ ] Payment appears in database
- [ ] Student enrollment activated after payment
- [ ] Email notifications working (if configured)
- [ ] Manual payment process tested
- [ ] Admin payment verification working
- [ ] Production API keys obtained
- [ ] Updated .env with production keys
- [ ] Tested on live site with small amount

---

## 🆘 Troubleshooting

### "Payment provider unavailable"

**Cause**: API keys not configured  
**Solution**: Add keys to `.env` file and restart Apache

### "All payment providers failed"

**Cause**: All providers having issues or misconfigured  
**Solution**: Configure manual bank transfer as fallback

### "Xendit authentication failed"

**Cause**: Invalid or expired API keys  
**Solution**: Generate new keys from Xendit dashboard

### "PayMongo 403 error"

**Cause**: CloudFront blocking (see PAYMONGO_SETUP.md)  
**Solution**: System automatically falls back to Xendit

---

## 📞 Support

### Xendit Support
- Dashboard: https://dashboard.xendit.co/
- Docs: https://developers.xendit.co/
- Email: support@xendit.co
- Live Chat: Available in dashboard

### PayMongo Support
- Dashboard: https://dashboard.paymongo.com/
- Docs: https://developers.paymongo.com/
- Email: support@paymongo.com

---

## 🚀 Quick Start (TL;DR)

1. **Create Xendit account**: https://dashboard.xendit.co/register
2. **Get API keys** from Settings → Developers
3. **Add to .env**:
   ```env
   PAYMENT_PROVIDER=xendit
   XENDIT_SECRET_KEY=xnd_development_...
   XENDIT_PUBLIC_KEY=xnd_public_development_...
   XENDIT_TEST_MODE=true
   ```
4. **Restart Apache**
5. **Test enrollment** with GCash/PayMaya
6. **Done!** 🎉

---

For detailed PayMongo setup, see **[PAYMONGO_SETUP.md](PAYMONGO_SETUP.md)**  
For PayMongo 403 error fix, see **[PAYMONGO_FIX_SUMMARY.md](PAYMONGO_FIX_SUMMARY.md)**