# WhatsApp Payment System - Hi5ve MarketPlace

## Overview
The WhatsApp Payment System allows customers to initiate payments via WhatsApp and have admins manually confirm the payments once received. This is perfect for Nigerian markets where customers prefer to pay via bank transfers, mobile money, or cash deposits and send proof via WhatsApp.

## How It Works

### For Customers:
1. **Select WhatsApp Payment** during checkout
2. **Complete order** - system generates a payment reference
3. **Click WhatsApp link** - opens WhatsApp with pre-filled payment message
4. **Contact admin** via WhatsApp with payment details
5. **Send proof of payment** (screenshot, receipt, etc.)
6. **Wait for confirmation** - admin will confirm payment in the system

### For Admins:
1. **Receive WhatsApp message** from customer with payment request
2. **Provide payment details** (bank account, mobile money, etc.)
3. **Receive proof of payment** from customer
4. **Verify payment** in bank/mobile money account
5. **Confirm in admin panel** - go to Admin > Payments
6. **Update order status** - system automatically updates order to confirmed

## Admin Payment Management

### Viewing WhatsApp Payments
- Go to **Admin > Payments**
- Filter by **Gateway: WhatsApp** to see only WhatsApp payments
- **Pending** payments require admin action
- **Green badge** indicates WhatsApp payments

### Confirming Payments
1. Click **View** button on pending WhatsApp payment
2. Review payment details and customer information
3. Click **Confirm Payment** button
4. Add admin notes (optional)
5. Payment status changes to **Completed**
6. Order status automatically updates to **Confirmed**

### Rejecting Payments
1. Click **View** button on pending WhatsApp payment
2. Click **Reject Payment** button
3. Enter rejection reason
4. Payment status changes to **Failed**
5. Customer can be notified via WhatsApp

## Payment Flow

```
Customer Checkout → WhatsApp Message → Admin Verification → Payment Confirmation → Order Processing
```

## Database Tables

### payments
- Stores all payment records including WhatsApp payments
- `gateway = 'whatsapp'` for WhatsApp payments
- `status = 'pending'` until admin confirms

### payment_methods
- Contains WhatsApp payment method configuration
- `name = 'whatsapp_payment'`
- `gateway = 'whatsapp'`

## Configuration

### WhatsApp Business Number
Update the WhatsApp business number in:
- `includes/functions.php` - `getWhatsAppLink()` function
- Currently set to: `+2348123456789`

### Payment Instructions
Customize the WhatsApp message template in:
- `classes/PaymentGateway.php` - `generateWhatsAppPaymentMessage()` method

## Features

### ✅ Implemented
- WhatsApp payment method in checkout
- Automatic WhatsApp link generation
- Admin confirmation system
- Payment tracking and history
- Order status integration
- Professional admin interface

### 🔄 Workflow
1. Customer selects WhatsApp payment
2. System generates payment reference
3. WhatsApp link opens with payment message
4. Admin receives payment via WhatsApp
5. Admin confirms in system
6. Order automatically confirmed

### 📱 Mobile Optimized
- WhatsApp links work on mobile devices
- Responsive admin interface
- Touch-friendly confirmation buttons

## Security

- Payment references are unique and timestamped
- Admin authentication required for confirmations
- All actions are logged with timestamps
- Gateway response includes admin details

## Testing

Access the system at:
- **Customer Checkout**: `http://localhost/mart3/checkout.php`
- **Admin Payments**: `http://localhost/mart3/admin/payments.php`

## Support

For technical support or customization:
- Check payment logs in database
- Review admin action history
- Contact system administrator

---

**Note**: This system is designed for Nigerian market preferences where WhatsApp is the primary communication channel for business transactions. 