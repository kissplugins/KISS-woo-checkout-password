<?php
/**
 * Plugin Name: KISS Woo Checkout Password Protection
 * Plugin URI: https://github.com/your-repo/wc-checkout-password-protection
 * Description: Password-protects the WooCommerce checkout page on specified URLs (dev/staging environments). Uses a URL whitelist approach - if the current site URL doesn't match any defined URLs, no password is required (safe for production).
 * Version: 1.0.0
 * Author: KISS Plugins
 * Author URI: https://github.com/kissplugins/KISS-woo-checkout-password
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-checkout-password-protection
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package WC_Checkout_Password_Protection
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * Uses singleton pattern to ensure single instance.
 * Implements URL whitelist approach for maximum production safety.
 *
 * @since 1.0.0
 */
final class WC_Checkout_Password_Protection {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Option name for protected URLs.
	 *
	 * @var string
	 */
	const OPTION_URLS = 'wccpp_protected_urls';

	/**
	 * Option name for the password hash.
	 *
	 * @var string
	 */
	const OPTION_PASSWORD = 'wccpp_password_hash';

	/**
	 * Cookie name for authentication.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'wccpp_checkout_auth';

	/**
	 * Cookie expiration in seconds (24 hours).
	 *
	 * @var int
	 */
	const COOKIE_EXPIRATION = DAY_IN_SECONDS;

	/**
	 * Single instance of the class.
	 *
	 * @var WC_Checkout_Password_Protection|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 * @return WC_Checkout_Password_Protection
	 */
	public static function get_instance(): WC_Checkout_Password_Protection {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Private to enforce singleton.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize all hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks(): void {
		// Check WooCommerce dependency.
		add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ) );

		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Frontend checkout protection.
		add_action( 'template_redirect', array( $this, 'maybe_protect_checkout' ), 5 );
		add_action( 'wp_ajax_nopriv_wccpp_verify_password', array( $this, 'ajax_verify_password' ) );
		add_action( 'wp_ajax_wccpp_verify_password', array( $this, 'ajax_verify_password' ) );

		// Load translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// HPOS compatibility declaration.
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_woocommerce(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Display admin notice if WooCommerce is missing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: WooCommerce plugin name */
					esc_html__( '%s requires WooCommerce to be installed and active.', 'wc-checkout-password-protection' ),
					'<strong>WooCommerce Checkout Password Protection</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Declare HPOS compatibility.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wc-checkout-password-protection',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wccpp-settings' ) ),
			esc_html__( 'Settings', 'wc-checkout-password-protection' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add admin menu under WooCommerce.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Checkout Password Protection', 'wc-checkout-password-protection' ),
			__( 'Checkout Password', 'wc-checkout-password-protection' ),
			'manage_woocommerce',
			'wccpp-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings using Settings API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings(): void {
		// Register settings.
		register_setting(
			'wccpp_settings_group',
			self::OPTION_URLS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_urls' ),
				'default'           => array(),
			)
		);

		register_setting(
			'wccpp_settings_group',
			self::OPTION_PASSWORD,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_password' ),
				'default'           => '',
			)
		);

		// Add settings section.
		add_settings_section(
			'wccpp_main_section',
			__( 'Checkout Protection Settings', 'wc-checkout-password-protection' ),
			array( $this, 'render_section_description' ),
			'wccpp-settings'
		);

		// Add settings fields.
		add_settings_field(
			'wccpp_urls_field',
			__( 'Protected URLs', 'wc-checkout-password-protection' ),
			array( $this, 'render_urls_field' ),
			'wccpp-settings',
			'wccpp_main_section'
		);

		add_settings_field(
			'wccpp_password_field',
			__( 'Checkout Password', 'wc-checkout-password-protection' ),
			array( $this, 'render_password_field' ),
			'wccpp-settings',
			'wccpp_main_section'
		);

		add_settings_field(
			'wccpp_status_field',
			__( 'Current Status', 'wc-checkout-password-protection' ),
			array( $this, 'render_status_field' ),
			'wccpp-settings',
			'wccpp_main_section'
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_styles( string $hook ): void {
		if ( 'woocommerce_page_wccpp-settings' !== $hook ) {
			return;
		}

		wp_add_inline_style(
			'wp-admin',
			'
			.wccpp-status-badge {
				display: inline-block;
				padding: 4px 12px;
				border-radius: 4px;
				font-weight: 600;
				font-size: 13px;
			}
			.wccpp-status-protected {
				background: #fef3cd;
				color: #856404;
				border: 1px solid #ffc107;
			}
			.wccpp-status-unprotected {
				background: #d4edda;
				color: #155724;
				border: 1px solid #28a745;
			}
			.wccpp-info-box {
				background: #f0f6fc;
				border-left: 4px solid #0073aa;
				padding: 12px 16px;
				margin: 10px 0;
			}
			.wccpp-warning-box {
				background: #fff8e5;
				border-left: 4px solid #ffb900;
				padding: 12px 16px;
				margin: 10px 0;
			}
			'
		);
	}

	/**
	 * Render settings section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_section_description(): void {
		?>
		<div class="wccpp-info-box">
			<p>
				<strong><?php esc_html_e( 'How it works:', 'wc-checkout-password-protection' ); ?></strong>
				<?php esc_html_e( 'This plugin uses a URL whitelist approach for maximum safety. Only sites whose URLs match the list below will require a password to checkout.', 'wc-checkout-password-protection' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Safe by default:', 'wc-checkout-password-protection' ); ?></strong>
				<?php esc_html_e( 'If no URLs are defined, or if the current site URL doesn\'t match any listed URL, checkout works normally without a password. This prevents accidental lockouts on production sites.', 'wc-checkout-password-protection' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the protected URLs field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_urls_field(): void {
		$urls = get_option( self::OPTION_URLS, array() );
		$urls_text = is_array( $urls ) ? implode( "\n", $urls ) : '';
		?>
		<textarea
			name="<?php echo esc_attr( self::OPTION_URLS ); ?>"
			id="wccpp_urls"
			rows="6"
			cols="50"
			class="large-text code"
			placeholder="dev.example.com&#10;staging.example.com&#10;localhost"
		><?php echo esc_textarea( $urls_text ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Enter one URL per line. Include only the domain (e.g., "staging.example.com" or "localhost:8080"). Do not include http:// or https://.', 'wc-checkout-password-protection' ); ?>
		</p>
		<p class="description">
			<strong><?php esc_html_e( 'Current site URL:', 'wc-checkout-password-protection' ); ?></strong>
			<code><?php echo esc_html( $this->get_current_host() ); ?></code>
		</p>
		<?php
	}

	/**
	 * Render the password field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_password_field(): void {
		$has_password = ! empty( get_option( self::OPTION_PASSWORD ) );
		?>
		<input
			type="password"
			name="<?php echo esc_attr( self::OPTION_PASSWORD ); ?>"
			id="wccpp_password"
			class="regular-text"
			placeholder="<?php echo $has_password ? esc_attr__( '••••••••', 'wc-checkout-password-protection' ) : ''; ?>"
			autocomplete="new-password"
		>
		<p class="description">
			<?php if ( $has_password ) : ?>
				<?php esc_html_e( 'Leave blank to keep the current password. Enter a new value to change it.', 'wc-checkout-password-protection' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Set the password that visitors will need to enter to access checkout on protected sites.', 'wc-checkout-password-protection' ); ?>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Render the current status field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_status_field(): void {
		$is_protected = $this->is_current_site_protected();
		$has_password = ! empty( get_option( self::OPTION_PASSWORD ) );
		?>
		<div>
			<?php if ( $is_protected && $has_password ) : ?>
				<span class="wccpp-status-badge wccpp-status-protected">
					<?php esc_html_e( '🔒 Checkout is PASSWORD PROTECTED on this site', 'wc-checkout-password-protection' ); ?>
				</span>
				<div class="wccpp-warning-box" style="margin-top: 10px;">
					<p>
						<?php esc_html_e( 'Visitors will need to enter the password before they can complete checkout.', 'wc-checkout-password-protection' ); ?>
					</p>
				</div>
			<?php elseif ( $is_protected && ! $has_password ) : ?>
				<span class="wccpp-status-badge wccpp-status-protected">
					<?php esc_html_e( '⚠️ URL matched but NO PASSWORD SET', 'wc-checkout-password-protection' ); ?>
				</span>
				<div class="wccpp-warning-box" style="margin-top: 10px;">
					<p>
						<?php esc_html_e( 'This site\'s URL matches the protected list, but no password has been set. Please set a password above.', 'wc-checkout-password-protection' ); ?>
					</p>
				</div>
			<?php else : ?>
				<span class="wccpp-status-badge wccpp-status-unprotected">
					<?php esc_html_e( '✓ Checkout is OPEN (no password required)', 'wc-checkout-password-protection' ); ?>
				</span>
				<p class="description" style="margin-top: 10px;">
					<?php esc_html_e( 'This site\'s URL does not match any protected URLs. Checkout works normally.', 'wc-checkout-password-protection' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		// Check user capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wc-checkout-password-protection' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce Checkout Password Protection', 'wc-checkout-password-protection' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wccpp_settings_group' );
				do_settings_sections( 'wccpp-settings' );
				submit_button( __( 'Save Settings', 'wc-checkout-password-protection' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize the URLs input.
	 *
	 * @since 1.0.0
	 * @param mixed $input Raw input value (string from textarea).
	 * @return array Sanitized array of URLs.
	 */
	public function sanitize_urls( $input ): array {
		if ( empty( $input ) ) {
			return array();
		}

		// Handle string input from textarea.
		if ( is_string( $input ) ) {
			$urls = array_filter(
				array_map( 'trim', explode( "\n", $input ) )
			);
		} elseif ( is_array( $input ) ) {
			$urls = array_filter( array_map( 'trim', $input ) );
		} else {
			return array();
		}

		$sanitized = array();
		foreach ( $urls as $url ) {
			// Remove protocol if present.
			$url = preg_replace( '#^https?://#i', '', $url );
			// Remove trailing slashes and paths.
			$url = strtok( $url, '/' );
			// Sanitize and validate.
			$url = sanitize_text_field( $url );
			if ( ! empty( $url ) ) {
				$sanitized[] = strtolower( $url );
			}
		}

		return array_unique( $sanitized );
	}

	/**
	 * Sanitize the password input.
	 *
	 * @since 1.0.0
	 * @param mixed $input Raw password input.
	 * @return string Hashed password or existing hash if empty.
	 */
	public function sanitize_password( $input ): string {
		// If empty, keep existing password.
		if ( empty( $input ) ) {
			return get_option( self::OPTION_PASSWORD, '' );
		}

		// Hash the new password using WP's password hashing.
		return wp_hash_password( $input );
	}

	/**
	 * Get the current site host.
	 *
	 * @since 1.0.0
	 * @return string Current host (domain with port if non-standard).
	 */
	private function get_current_host(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		return strtolower( $host );
	}

	/**
	 * Check if the current site URL matches any protected URL.
	 *
	 * @since 1.0.0
	 * @return bool True if current site should be protected.
	 */
	private function is_current_site_protected(): bool {
		$protected_urls = get_option( self::OPTION_URLS, array() );

		if ( empty( $protected_urls ) || ! is_array( $protected_urls ) ) {
			return false;
		}

		$current_host = $this->get_current_host();

		foreach ( $protected_urls as $protected_url ) {
			// Exact match.
			if ( $current_host === strtolower( $protected_url ) ) {
				return true;
			}

			// Wildcard subdomain match (e.g., *.example.com).
			if ( str_starts_with( $protected_url, '*.' ) ) {
				$domain = substr( $protected_url, 2 );
				if ( str_ends_with( $current_host, '.' . $domain ) || $current_host === $domain ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if user has valid authentication cookie.
	 *
	 * @since 1.0.0
	 * @return bool True if user has valid auth cookie.
	 */
	private function has_valid_auth_cookie(): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$stored_hash  = get_option( self::OPTION_PASSWORD );

		if ( empty( $stored_hash ) ) {
			return false;
		}

		// Cookie contains a hash of: password_hash + site_url + expiration.
		// This prevents cookie reuse across different sites.
		$parts = explode( '|', $cookie_value );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $token, $expiration ) = $parts;

		// Check expiration.
		if ( (int) $expiration < time() ) {
			return false;
		}

		// Verify token.
		$expected_token = $this->generate_auth_token( $stored_hash, (int) $expiration );

		return hash_equals( $expected_token, $token );
	}

	/**
	 * Generate authentication token.
	 *
	 * @since 1.0.0
	 * @param string $password_hash The stored password hash.
	 * @param int    $expiration    Token expiration timestamp.
	 * @return string Authentication token.
	 */
	private function generate_auth_token( string $password_hash, int $expiration ): string {
		$data = $password_hash . '|' . get_site_url() . '|' . $expiration;
		return wp_hash( $data, 'auth' );
	}

	/**
	 * Set authentication cookie.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function set_auth_cookie(): void {
		$stored_hash = get_option( self::OPTION_PASSWORD );
		$expiration  = time() + self::COOKIE_EXPIRATION;
		$token       = $this->generate_auth_token( $stored_hash, $expiration );
		$cookie_value = $token . '|' . $expiration;

		// Set secure cookie.
		setcookie(
			self::COOKIE_NAME,
			$cookie_value,
			array(
				'expires'  => $expiration,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);
	}

	/**
	 * Maybe protect the checkout page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_protect_checkout(): void {
		// Only run on checkout page.
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		// Don't protect AJAX requests or order-received endpoint.
		if ( wp_doing_ajax() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Check if this site should be protected.
		if ( ! $this->is_current_site_protected() ) {
			return;
		}

		// Check if password is set.
		$stored_hash = get_option( self::OPTION_PASSWORD );
		if ( empty( $stored_hash ) ) {
			return;
		}

		// Check for valid auth cookie.
		if ( $this->has_valid_auth_cookie() ) {
			return;
		}

		// Handle form submission.
		if ( $this->handle_password_submission() ) {
			return;
		}

		// Show password form.
		$this->display_password_form();
		exit;
	}

	/**
	 * Handle password form submission.
	 *
	 * @since 1.0.0
	 * @return bool True if password was correct and cookie was set.
	 */
	private function handle_password_submission(): bool {
		// Check for POST submission.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return false;
		}

		// Verify nonce.
		if ( ! isset( $_POST['wccpp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccpp_nonce'] ) ), 'wccpp_verify_password' ) ) {
			return false;
		}

		// Check for password field.
		if ( ! isset( $_POST['wccpp_password'] ) ) {
			return false;
		}

		$submitted_password = sanitize_text_field( wp_unslash( $_POST['wccpp_password'] ) );
		$stored_hash        = get_option( self::OPTION_PASSWORD );

		// Verify password.
		if ( wp_check_password( $submitted_password, $stored_hash ) ) {
			$this->set_auth_cookie();
			// Redirect to prevent form resubmission.
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		return false;
	}

	/**
	 * AJAX handler for password verification.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_verify_password(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( 'wccpp_verify_password', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wc-checkout-password-protection' ) ) );
		}

		// Check for password.
		if ( ! isset( $_POST['password'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a password.', 'wc-checkout-password-protection' ) ) );
		}

		$submitted_password = sanitize_text_field( wp_unslash( $_POST['password'] ) );
		$stored_hash        = get_option( self::OPTION_PASSWORD );

		// Verify password.
		if ( wp_check_password( $submitted_password, $stored_hash ) ) {
			$this->set_auth_cookie();
			wp_send_json_success( array( 'redirect' => wc_get_checkout_url() ) );
		}

		wp_send_json_error( array( 'message' => __( 'Incorrect password. Please try again.', 'wc-checkout-password-protection' ) ) );
	}

	/**
	 * Display the password protection form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function display_password_form(): void {
		// Check for failed attempt.
		$error_message = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wccpp_password'] ) ) {
			$error_message = __( 'Incorrect password. Please try again.', 'wc-checkout-password-protection' );
		}

		// Get header.
		get_header( 'shop' );
		?>
		<style>
			.wccpp-password-form-wrapper {
				max-width: 500px;
				margin: 60px auto;
				padding: 40px;
				background: #fff;
				border-radius: 8px;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				text-align: center;
			}
			.wccpp-password-form-wrapper h2 {
				margin-bottom: 10px;
				color: #333;
			}
			.wccpp-password-form-wrapper .description {
				color: #666;
				margin-bottom: 30px;
				font-size: 15px;
			}
			.wccpp-password-form-wrapper form {
				display: flex;
				flex-direction: column;
				gap: 15px;
			}
			.wccpp-password-form-wrapper input[type="password"] {
				padding: 12px 16px;
				font-size: 16px;
				border: 1px solid #ddd;
				border-radius: 4px;
				width: 100%;
				box-sizing: border-box;
			}
			.wccpp-password-form-wrapper input[type="password"]:focus {
				border-color: #0073aa;
				outline: none;
				box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
			}
			.wccpp-password-form-wrapper button {
				padding: 12px 24px;
				font-size: 16px;
				background: #0073aa;
				color: #fff;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				transition: background 0.2s;
			}
			.wccpp-password-form-wrapper button:hover {
				background: #005177;
			}
			.wccpp-password-form-wrapper .error-message {
				color: #dc3545;
				background: #ffe6e6;
				padding: 10px 15px;
				border-radius: 4px;
				margin-bottom: 15px;
			}
			.wccpp-password-form-wrapper .notice-text {
				font-size: 13px;
				color: #999;
				margin-top: 20px;
			}
		</style>

		<div class="wccpp-password-form-wrapper">
			<h2><?php esc_html_e( 'Password Required', 'wc-checkout-password-protection' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'This checkout page is password protected. Please enter the password to continue.', 'wc-checkout-password-protection' ); ?>
			</p>

			<?php if ( ! empty( $error_message ) ) : ?>
				<div class="error-message">
					<?php echo esc_html( $error_message ); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'wccpp_verify_password', 'wccpp_nonce' ); ?>
				<input
					type="password"
					name="wccpp_password"
					placeholder="<?php esc_attr_e( 'Enter password', 'wc-checkout-password-protection' ); ?>"
					required
					autocomplete="off"
					autofocus
				>
				<button type="submit">
					<?php esc_html_e( 'Submit', 'wc-checkout-password-protection' ); ?>
				</button>
			</form>

			<p class="notice-text">
				<?php esc_html_e( 'This is a development/staging environment. If you reached this page by mistake, please visit the main website.', 'wc-checkout-password-protection' ); ?>
			</p>
		</div>
		<?php
		get_footer( 'shop' );
	}
}

// Initialize the plugin.
WC_Checkout_Password_Protection::get_instance();
