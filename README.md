# WC Membership Product

A custom WooCommerce plugin that adds a **"Membership"** product type with duration-based access control, conditional checkout fields, and automated expiration handling. The solution is designed to enable content restriction and subscription-style access while utilizing best-practice WooCommerce integration techniques.

---

## ✨ Features

* **Custom Product Type:** Registers a new "Membership" product type within WooCommerce, extending the `WC_Product` class with duration and tier configuration.

* **Duration-Based Access:** Supports flexible membership durations (days, weeks, months, years) with automatic expiration date calculation upon purchase.

* **Conditional Checkout Fields:** Displays custom fields (text, select, checkbox) only when a membership product is in the cart, with full validation and storage.

* **Content Restriction:** Restricts content via `[wcmp_restricted]` shortcode or a simple meta box checkbox on posts/pages. Non-members see a customizable restriction message.

* **Automated Access Management:** Grants membership access automatically on order completion and expires memberships via daily cron job with email notifications.

* **Professional Admin Interface:** Full WP_List_Table implementation for membership management with filtering, sorting, and bulk actions (revoke, extend, reactivate).

* **Custom Database Tables:** Uses dedicated tables (`wp_wcmp_memberships`, `wp_wcmp_checkout_fields`) with proper indexing for performant queries — no meta table bloat.

* **Extensibility Hooks:** Provides action hooks (`wcmp_membership_granted`, `wcmp_membership_expired`, `wcmp_membership_revoked`) and filters for third-party integration.

* **HPOS Compatible:** Declares compatibility with WooCommerce High-Performance Order Storage.

---

## 🛠 Installation

### For End-Users (Packaged Plugin)

1. Download the **.zip** file from the latest release: **[Click here to download the latest release](https://github.com/dilipraghavan/wc-membership-product/releases)**.
2. In the WordPress dashboard, go to **Plugins** → **Add New**.
3. Click **Upload Plugin**, select the downloaded **.zip** file, and click **Install Now**.
4. After installation, click **Activate Plugin**.

### For Developers (Standard Git)

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/dilipraghavan/wc-membership-product.git wp-content/plugins/wc-membership-product
   ```

2. **Install Dependencies:**

   ```bash
   cd wp-content/plugins/wc-membership-product
   composer install
   ```

3. **Activate Plugin:** Activate from the WordPress Plugins screen.

---

## ⚙️ Configuration

### Step 1: Create a Membership Product

1. Navigate to **Products** → **Add New**.
2. Select **Membership** from the product type dropdown.
3. Set the **Regular Price**.
4. Go to the **Membership** tab and configure:
   - **Duration:** e.g., 1 Year
   - **Tier:** Standard, Premium, or VIP
5. Publish the product.

### Step 2: Restrict Content

**Option A: Shortcode**

Wrap content in the `[wcmp_restricted]` shortcode:

```
[wcmp_restricted]
This content is only visible to members.
[/wcmp_restricted]
```

For specific membership products:

```
[wcmp_restricted product_id="123"]
Only users with this specific membership can see this.
[/wcmp_restricted]
```

**Option B: Meta Box**

1. Edit any post or page.
2. Find the **Membership Restriction** meta box in the sidebar.
3. Check **Restrict to members only**.
4. Optionally select a specific membership product.
5. Save.

---

## 🚀 Usage

### Purchasing a Membership

1. Customer adds membership product to cart.
2. Conditional checkout fields appear (Company Name, Referral Source, Terms Agreement).
3. On order completion, membership access is granted automatically.
4. Customer can now view restricted content.

### Managing Memberships

Navigate to **WooCommerce** → **Memberships** to:

- View all memberships with status, dates, and linked orders
- Filter by status (Active, Expired, Cancelled) or product
- **Revoke** an active membership
- **Extend** a membership by a custom duration
- **Reactivate** an expired or cancelled membership

### Automatic Expiration

A daily cron job checks for expired memberships and:
- Updates status to "expired"
- Sends notification email to the user
- Fires `wcmp_membership_expired` action hook

---

## 🗄 Database Schema

### wp_wcmp_memberships

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | WordPress user ID |
| product_id | bigint | Membership product ID |
| order_id | bigint | WooCommerce order ID |
| tier | varchar | Membership tier (standard/premium/vip) |
| status | varchar | Status (active/expired/cancelled) |
| started_at | datetime | Membership start date |
| expires_at | datetime | Membership expiration date |
| created_at | datetime | Record creation date |
| updated_at | datetime | Record last update |

### wp_wcmp_checkout_fields

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| order_id | bigint | WooCommerce order ID |
| field_key | varchar | Field identifier |
| field_value | text | Field value |
| created_at | datetime | Record creation date |

---

## 🔧 Developer Hooks

### Actions

```php
// Fired when membership is granted
do_action( 'wcmp_membership_granted', $membership_id, $user_id, $product_id, $order_id );

// Fired when membership expires
do_action( 'wcmp_membership_expired', $membership_id, $user_id );

// Fired when membership is revoked
do_action( 'wcmp_membership_revoked', $membership_id, $user_id );
```

### Filters

```php
// Customize checkout fields
apply_filters( 'wcmp_checkout_fields', $fields, $product );

// Override access check
apply_filters( 'wcmp_has_access', $has_access, $user_id, $content_id, $product_id );

// Customize restricted message
apply_filters( 'wcmp_restricted_message', $message );

// Control expiration email
apply_filters( 'wcmp_send_expiration_email', $send, $membership );
```

---

## 📊 Viewing Membership Data

To view membership records directly, inspect the **Order Notes** on individual orders or query the database:

```sql
SELECT * FROM wp_wcmp_memberships WHERE status = 'active';
```

---

## 🤝 Contributing

Contributions are welcome! If you find a bug or have a suggestion, please open an issue or submit a pull request on the GitHub repository.

**GitHub Repository:** https://github.com/dilipraghavan/wc-membership-product

---

## License

This project is licensed under the MIT License. See the **[LICENSE file](https://github.com/dilipraghavan/wc-membership-product/blob/main/LICENSE)** for details.
