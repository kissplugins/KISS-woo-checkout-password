=== WooCommerce Checkout Password Protection ===
Contributors: yourname
Tags: woocommerce, checkout, password, protection, staging, development
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Password-protect your WooCommerce checkout on development and staging sites. Safe by default - production sites are never affected.

== Description ==

**Prevent accidental orders on your dev/staging sites!**

This plugin uses a URL whitelist approach to password-protect the WooCommerce checkout page on specified environments. If someone accidentally discovers your staging site and tries to checkout, they'll be prompted for a password first.

= Safe by Default =

The plugin uses a **whitelist approach**, meaning:

* You define which URLs should be protected (e.g., `staging.example.com`, `dev.example.com`)
* If the current site URL doesn't match any listed URL, checkout works normally
* An empty URL list means NO protection anywhere
* **This prevents accidental lockouts on production sites**

= How It Works =

1. Install and activate the plugin
2. Go to WooCommerce → Checkout Password
3. Add your staging/dev URLs (one per line)
4. Set a password
5. Done! Checkout is now protected on those URLs

= Features =

* **URL Whitelist** - Only specified URLs require password
* **Secure Cookies** - Authentication persists for 24 hours
* **WP Password Hashing** - Uses WordPress's secure password system
* **HPOS Compatible** - Works with WooCommerce High-Performance Order Storage
* **Translation Ready** - Fully internationalized
* **Clean UI** - Matches your theme's checkout styling
* **Admin Status** - Clearly shows if current site is protected

= Use Cases =

* Development environments
* Staging/QA sites
* Client preview sites
* Internal testing environments

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress
3. Go to WooCommerce → Checkout Password
4. Configure your protected URLs and password

== Frequently Asked Questions ==

= What happens if I don't add any URLs? =

Nothing! Checkout works normally on all sites. The plugin only activates when the current site URL matches one of your defined URLs.

= What happens if I forget to set a password? =

The plugin requires both a matching URL AND a password to activate protection. If no password is set, checkout works normally (with an admin warning).

= Can I use wildcards? =

Yes! Use `*.example.com` to match all subdomains of example.com.

= Does this work with block checkout? =

The plugin intercepts the checkout page via `template_redirect`, which works with both classic and block checkout.

= How long does the password last? =

Once entered correctly, a secure cookie is set for 24 hours. After that, the password must be re-entered.

= Is this secure for production use? =

This plugin is designed for dev/staging protection, not for securing actual e-commerce transactions. For production password protection, consider WooCommerce's built-in private products or membership plugins.

== Screenshots ==

1. Admin settings page showing protected URL configuration
2. Frontend password form on checkout
3. Status indicator showing protection is active

== Changelog ==

= 1.0.0 =
* Initial release
* URL whitelist protection system
* Secure cookie-based authentication
* WordPress Settings API integration
* HPOS compatibility
* Full internationalization support

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and configure your protected URLs.
