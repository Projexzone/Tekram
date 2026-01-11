# Tekram - Market Management System

**Tekram** (Market Stall backwards) is a comprehensive vendor and market management system for markets, festivals, and events with mobile check-in and vendor directory features.

## 🎯 Core Features

### **Vendor Management**
- Online application forms (custom HTML, no form builders)
- Approval/decline workflows  
- Vendor profiles with business information
- Logo/photo uploads
- Social media links (Facebook, Instagram, Website)
- Document management

### **Market Management**
- Create unlimited markets/events
- Set capacity and pricing
- Recurring markets (weekly, monthly, etc.)
- Booking windows
- Site/stall assignments

### **Booking System**
- Real-time availability checking
- Pre-booking days/weeks in advance
- Booking references
- Status management (pending/confirmed/cancelled)
- Waitlist management

### **Payment Processing**
- Stripe integration
- PayPal integration
- Manual payments (cash/card/bank transfer)
- Payment tracking (paid/partial/unpaid)
- On-site payment collection

### **📱 iPad Check-In Feature (NEW!)**
- Mobile-friendly check-in interface
- Filter by market and date
- Real-time stats (total/checked-in/unpaid)
- One-tap check-in
- On-site payment recording
- Visual payment status indicators
- Perfect for market day staff on iPads

### **👥 Sellers Directory (NEW!)**
- Public vendor directory page
- Shows all vendors attending specific markets
- Displays business info, products, logos
- Social media integration
- Automatic updates when vendors book
- Can filter by market/date
- SEO-friendly /sellers page

### **Communication**
- Automated email notifications
- Booking confirmations
- Payment receipts
- Custom email templates

### **Reporting & Analytics**
- Dashboard with key metrics
- Booking reports
- Revenue tracking
- Vendor statistics

---

## 🚀 Installation

### Via cPanel (Recommended):

1. **Upload files:**
   - Log in to cPanel → File Manager
   - Go to: `/public_html/wp-content/plugins/`
   - Upload `tekram.zip`
   - Extract the ZIP file
   - Delete the ZIP

2. **Activate:**
   - WordPress Admin → Plugins
   - Find "Tekram"
   - Click "Activate"

3. **Configure:**
   - Go to: Tekram → Settings
   - Set currency, formats, email settings
   - Save changes

---

## 📝 Shortcodes

### **[lt_application_form]**
Vendor application form with custom HTML fields

**Usage:**
```
[lt_application_form]
```

### **[lt_booking_form]**  
Booking form for registered vendors

**Usage:**
```
[lt_booking_form]
```

### **[lt_vendor_dashboard]**
Vendor dashboard showing bookings and payments

**Usage:**
```
[lt_vendor_dashboard]
```

### **[lt_event_list]**
Display upcoming markets

**Usage:**
```
[lt_event_list]
```

With options:
```
[lt_event_list limit="10" upcoming_only="yes"]
```

### **[lt_sellers]** ⭐ NEW!
Display all vendors attending markets

**Usage:**
```
[lt_sellers]
```

**With filters:**
```
[lt_sellers event_id="123"]
[lt_sellers date="2025-01-15"]
[lt_sellers event_id="123" date="2025-01-15"]
```

**Show all vendors (not just upcoming):**
```
[lt_sellers show_all="yes"]
```

---

## 📱 iPad Check-In Workflow

### **Pre-Market Setup:**
1. Vendors book online days/weeks in advance
2. System tracks payment status
3. Admin can see who's booked

### **On Market Day:**

1. **Access Check-In Interface:**
   - Tekram → 📱 Check-In
   - Or direct link: `/wp-admin/admin.php?page=tekram-checkin`

2. **Select Market & Date:**
   - Choose the market from dropdown
   - Select today's date
   - Click "Load Vendors"

3. **View Stats:**
   - Total Bookings
   - Checked In count
   - Unpaid count

4. **Check In Vendors:**
   - Find vendor in list
   - If they haven't paid: Click "💰 Collect Payment"
     - Enter payment method (cash/card)
     - System records payment
     - Auto-checks them in
   - If already paid: Click "✓ Check In"
   - Card turns green when checked in

### **iPad Setup Tips:**
- Use Safari or Chrome
- Bookmark the check-in page
- Enable "Add to Home Screen" for app-like experience
- Works offline-ish (loads previously viewed data)
- Best on iPad but works on any tablet/phone

---

## 👥 Sellers Directory Setup

### **Create the Sellers Page:**

1. **Create New Page:**
   - Pages → Add New
   - Title: "Our Sellers" or "Market Vendors"
   - Slug: `/sellers`

2. **Add Shortcode:**
   ```
   [lt_sellers]
   ```

3. **Publish**

### **What Displays:**
- Vendor business name
- Product/service description
- Logo/photo (if uploaded)
- Social media links
- Grouped by market and date
- Only shows vendors with confirmed/paid bookings

### **Vendor Information Shown:**
The system automatically pulls from their application:
- Business Name
- Products Description
- Logo (featured image)
- Website URL (custom field)
- Facebook URL (custom field)
- Instagram URL (custom field)

### **Adding Social Media Links:**
1. Edit vendor profile in admin
2. Add custom fields:
   - `_lt_website` - Full URL
   - `_lt_facebook` - Full Facebook URL
   - `_lt_instagram` - Full Instagram URL

Or add this to vendor edit screen (coming in future update).

---

## 🎨 Customization

### **Styling the Sellers Page:**
Add to your theme's CSS:

```css
/* Customize vendor cards */
.lt-seller-card {
    border: 2px solid #your-color;
    /* Your custom styles */
}

/* Customize vendor names */
.lt-seller-name {
    color: #your-brand-color;
    font-size: 24px;
}

/* Customize social icons */
.lt-social-link:hover {
    background: #your-brand-color;
}
```

### **Customizing Check-In Interface:**
The check-in page has inline styles that can be overridden in your theme or via admin CSS customizer.

---

## 💳 Payment Gateway Setup

### **Stripe:**
1. Create account at stripe.com
2. Get API keys
3. Tekram → Settings → Payments
4. Add keys and enable

### **PayPal:**
1. Create business account
2. Get credentials from developer.paypal.com
3. Add to settings and enable

### **Manual Payments:**
Already enabled! No setup needed.

---

## 🔧 File Structure

```
tekram/
├── tekram.php              # Main plugin file
├── includes/                     # Core functionality
│   ├── class-lt-database.php    # Database operations
│   ├── class-lt-vendor.php      # Vendor management
│   ├── class-lt-event.php       # Market management
│   ├── class-lt-booking.php     # Booking system
│   ├── class-lt-payment.php     # Payment processing
│   ├── class-lt-notifications.php # Email system
│   └── class-lt-site-map.php    # Site management
├── admin/                        # Admin panel
│   ├── class-lt-admin.php
│   ├── class-lt-admin-dashboard.php
│   ├── class-lt-admin-vendors.php
│   ├── class-lt-admin-events.php
│   ├── class-lt-admin-bookings.php
│   ├── class-lt-admin-checkin.php  # ⭐ iPad check-in
│   └── class-lt-admin-settings.php
├── public/                       # Frontend
│   ├── class-lt-public.php
│   └── class-lt-shortcodes.php  # ⭐ Includes sellers directory
├── assets/                       # CSS & JavaScript
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   └── js/
│       ├── admin.js
│       └── public.js
├── templates/                    # Email templates
│   └── emails/
│       └── booking-confirmation.php
└── README.md                     # This file
```

---

## 📊 Database Tables

Creates 6 custom tables on activation:

- `wp_lt_bookings` - All bookings
- `wp_lt_payments` - Payment records  
- `wp_lt_notifications` - Email queue
- `wp_lt_availability` - Site availability
- `wp_lt_documents` - Document uploads
- `wp_lt_waitlist` - Waitlist entries

---

## 🔐 User Roles

### **Market Vendor** (`lt_vendor`)
- View own bookings
- Make new bookings
- Update profile
- Upload documents

### **Market Coordinator** (`lt_coordinator`)
- Manage specific markets
- View bookings for their markets
- Limited admin access

### **Administrator**
- Full access to everything
- Check-in interface
- Settings management

---

## ❓ FAQ

### **Q: Can vendors pre-book weeks in advance?**
**A:** Yes! They can book as soon as the market's booking window opens (set in market settings).

### **Q: Can I collect payment on the day?**
**A:** Yes! Use the iPad check-in interface to record cash/card payments on-site.

### **Q: How do I add vendor social media links?**
**A:** Edit the vendor profile in admin and add custom fields `_lt_website`, `_lt_facebook`, `_lt_instagram`.

### **Q: Can the sellers page show only specific markets?**
**A:** Yes! Use: `[lt_sellers event_id="123"]`

### **Q: Does the check-in interface work on phones?**
**A:** Yes! It's responsive and works on any device, but optimized for tablets.

### **Q: Can vendors have logos on the sellers page?**
**A:** Yes! Upload a featured image to their vendor profile.

### **Q: How do I prevent double-bookings?**
**A:** The system automatically checks availability in real-time.

---

## 🆘 Troubleshooting

### **Sellers page showing old vendors:**
- Vendors only appear when they have confirmed/paid bookings
- Check booking status in admin
- Confirm booking dates are in the future (unless using `show_all="yes"`)

### **Check-in button not working:**
- Clear browser cache
- Check JavaScript console for errors
- Ensure user has admin permissions

### **Social media icons not showing:**
- Verify custom fields are added to vendor profile
- Check URLs are complete (include https://)

---

## 🎁 Resell & White-Label

You can:
- ✅ Rebrand completely
- ✅ Resell to multiple clients
- ✅ Customize per client
- ✅ Add your branding

Just update:
- Plugin header in `tekram.php`
- Email templates
- CSS colors/branding

---

## 📞 Support

For customization, support, or feature requests:
- Email: your-email@example.com
- Website: your-website.com

---

## 📝 Changelog

### Version 1.0.0
- Initial release
- Vendor application system
- Market management
- Booking system with real-time availability
- Payment processing (Stripe, PayPal, Manual)
- Email notifications
- Admin dashboard
- **📱 iPad check-in interface**
- **👥 Public sellers directory**
- Mobile-responsive design

---

**License:** GPL v2 or later  
**Developed by:** [Your Name/Company]  
**Version:** 1.0.0
