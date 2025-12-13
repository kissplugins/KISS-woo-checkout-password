# KISS WooCommerce Checkout Password Protection

**KISS = Keep It Simple (Stupid)** Plugins are designed to do one thing only. And we believe that one thing should be done very well.

The **KISS WooCommerce Checkout Password Protection** plugin does not have any upsell or freemium features. You get it all.

## Overview

Password-protect your WooCommerce checkout on development and staging sites. Safe by default - production sites are never affected.

**Prevent accidental orders on your dev/staging sites!**

This plugin uses a URL whitelist approach to password-protect the WooCommerce checkout page on specified environments. If someone accidentally discovers your staging site and tries to checkout, they'll be prompted for a password first.

## Safe by Default

The plugin uses a **whitelist approach**, meaning:

* You define which URLs should be protected (e.g., `staging.example.com`, `dev.example.com`)
* If the current site URL doesn't match any listed URL, checkout works normally
* An empty URL list means NO protection anywhere
* **This prevents accidental lockouts on production sites**

## Features

* **URL Whitelist** - Only specified URLs require password
* **Secure Cookies** - Authentication persists for 24 hours
* **WP Password Hashing** - Uses WordPress's secure password system
* **HPOS Compatible** - Works with WooCommerce High-Performance Order Storage
* **Translation Ready** - Fully internationalized
* **Clean UI** - Matches your theme's checkout styling
* **Admin Status** - Clearly shows if current site is protected

## Use Cases

* Development environments
* Staging/QA sites
* Client preview sites
* Internal testing environments

## Installation

1. Download the plugin file (if not already installed)
2. Go to your WordPress admin dashboard and navigate to **Plugins > Add New**
3. Click **Upload Plugin** and upload the `.zip` file
4. Once uploaded, click **Activate Plugin**

## How to Use

### 1. Access the Plugin Settings
1. Go to **WooCommerce → Checkout Password** in the WordPress admin dashboard
2. Alternatively, click the **Settings** link directly in the **Plugins** list

### 2. Configure Protected URLs
1. In the **Protected URLs** field, add your staging/dev URLs (one per line)
2. You can use wildcards: `*.example.com` to match all subdomains
3. Examples:
   - `staging.example.com`
   - `dev.example.com`
   - `*.staging.example.com`

### 3. Set a Password
1. Enter a strong password in the **Checkout Password** field
2. This password will be required to access checkout on protected URLs

### 4. Save Settings
Click **Save Settings** to apply your configuration

### 5. Test the Protection
1. Visit your staging/dev site's checkout page
2. You should see a password prompt
3. Enter the password you configured
4. Once authenticated, you can access checkout for 24 hours

## Frequently Asked Questions

**What happens if I don't add any URLs?**
Nothing! Checkout works normally on all sites. The plugin only activates when the current site URL matches one of your defined URLs.

**What happens if I forget to set a password?**
The plugin requires both a matching URL AND a password to activate protection. If no password is set, checkout works normally (with an admin warning).

**Can I use wildcards?**
Yes! Use `*.example.com` to match all subdomains of example.com.

**Does this work with block checkout?**
The plugin intercepts the checkout page via `template_redirect`, which works with both classic and block checkout.

**How long does the password last?**
Once entered correctly, a secure cookie is set for 24 hours. After that, the password must be re-entered.

**Is this secure for production use?**
This plugin is designed for dev/staging protection, not for securing actual e-commerce transactions. For production password protection, consider WooCommerce's built-in private products or membership plugins.

## Troubleshooting

* If you don't see the password prompt, verify that your current URL matches one of the protected URLs
* Check the admin settings page for the status indicator showing if protection is active
* Clear your browser cookies if you need to re-test the password prompt

## Road Map

Coming soon

## Questions & Customization Requests

Contact Us: devops@kissplugins.com | noel@kissplugins.com

## License

**This plugin is released under GPL v2**

This software is provided as-is **without** any warranties.
Please first review the code and test on a Development/Staging server.

Read the LICENSE file for more information in this Repo.

## Follow Us

**Follow Us on Blue Sky:**
https://bsky.app/profile/kissplugins.bsky.social

---

© Copyright Hypercart D.B.A. Neochrome, Inc.

