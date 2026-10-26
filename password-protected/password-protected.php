<?php
/*
Plugin Name: Password Protected
Plugin URI: https://passwordprotectedwp.com/
Description: A very simple way to quickly password protect your WordPress site with a single password. Please note: This plugin does not restrict access to uploaded files and images and does not work with some caching setups.
Version: 2.8.5
Author: Password Protected
Text Domain: password-protected
Author URI: https://passwordprotectedwp.com/
Requires at least: 4.9.6
Requires PHP: 5.6
Tested up to: 7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2 or (at your option)
any later version, as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @todo Use wp_hash_password() ?
 * @todo Remember me
 */


define( 'PASSWORD_PROTECTED_SUBDIR', '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) );
define( 'PASSWORD_PROTECTED_URL', plugins_url( PASSWORD_PROTECTED_SUBDIR ) );
define( 'PASSWORD_PROTECTED_DIR', plugin_dir_path( __FILE__ ) );

require_once PASSWORD_PROTECTED_DIR . 'includes/freemius.php';

global $Password_Protected;
$Password_Protected = new Password_Protected();

class Password_Protected {

	var $version 	   = '2.8.5';
	var $admin   	   = null;
	var $errors  	   = null;
	var $admin_caching = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->errors = new WP_Error();

		register_activation_hook( __FILE__, array( &$this, 'install' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );


		add_filter( 'password_protected_is_active', array( $this, 'allow_ip_addresses' ) );
		add_filter( 'password_protected_is_active', array( $this, 'elementor_compatibility' ) );

		add_action( 'init', array( $this, 'disable_caching' ), 1 );
		add_action( 'init', array( $this, 'maybe_process_logout' ), 1 );
		add_action( 'init', array( $this, 'maybe_process_login' ), 1 );
		add_action( 'wp', array( $this, 'disable_feeds' ) );
		add_action( 'template_redirect', array( $this, 'maybe_show_login' ), -10 );
		add_filter( 'pre_option_password_protected_status', array( $this, 'allow_feeds' ) );
		add_filter( 'pre_option_password_protected_status', array( $this, 'allow_administrators' ) );
		add_filter( 'pre_option_password_protected_status', array( $this, 'allow_users' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'only_allow_logged_in_rest_access' ) );
		add_action( 'init', array( $this, 'compat' ) );
		add_action( 'password_protected_login_messages', array( $this, 'login_messages' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'load_theme_stylesheet' ), 5 );

		add_action('password_protected_above_password_field', array( $this, 'password_protected_above_password_field' ));
		add_action('password_protected_below_password_field', array( $this, 'password_protected_below_password_field' ));


		

		// Available from WordPress 4.3+
		if ( function_exists( 'wp_site_icon' ) ) {
			add_action( 'password_protected_login_head', 'wp_site_icon' );
		}

		add_shortcode( 'password_protected_logout_link', array( $this, 'logout_link_shortcode' ) );

		include_once dirname( __FILE__ ) . '/admin/admin-bar.php';
		include_once dirname( __FILE__ ) . '/includes/compatibility.php';
		include_once dirname( __FILE__ ) . '/includes/class-password-protected-extension-compat.php';
		Password_Protected_Extension_Compat::init();
		if ( is_admin() ) {

			include_once dirname( __FILE__ ) . '/admin/admin-caching.php';
			include_once dirname( __FILE__ ) . '/admin/admin.php';

			$this->admin_caching = new Password_Protected_Admin_Caching( $this );
			$this->admin         = new Password_Protected_Admin();


		}
		include_once dirname( __FILE__ ) . '/admin/class-recaptcha.php';
		new Password_Protected_reCAPTCHA();

		include_once dirname( __FILE__ ) . '/includes/transient-functions.php';
		include_once dirname( __FILE__ ) . '/includes/activity-report-email/class-password-protected-activity-report-settings.php';

		include_once dirname( __FILE__ ) . '/admin/class-pp-all-captcha-tabs.php';
		Password_Protected_Free_allCaptchas::getInstance();

	}
	
	/**
	 * Disable Page Caching
	 */
	public function disable_caching() {

		if ( $this->is_active() && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

	}

	/**
	 * Is Active?
	 *
	 * @return  boolean  Is password protection active?
	 */
	public function is_active() {

		global $wp_query;

		// Always allow access to robots.txt
		if ( isset( $wp_query ) && is_robots() ) {
			return false;
		}

		if ( (bool) get_option( 'password_protected_status' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}

		$is_active = apply_filters( 'password_protected_is_active', $is_active );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Login page query arg forces protection UI.
		if ( isset( $_GET['password-protected'] ) ) {
			$is_active = true;
		}

		return $is_active;

	}

	/**
	 * Disable Feeds
	 *
	 * @todo  An option/filter to prevent disabling of feeds.
	 */
	public function disable_feeds() {

		if ( $this->is_active() ) {
			add_action( 'do_feed', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rdf', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rss', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rss2', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_atom', array( $this, 'disable_feed' ), 1 );
		}

	}

	/**
	 * Disable Feed
	 *
	 * @todo  Make Translatable
	 */
	public function disable_feed() {

		wp_die(
			wp_kses_post(
				sprintf(
					/* translators: %s: site URL. */
					__( 'Feeds are not available for this site. Please visit the <a href="%s">website</a>.', 'password-protected' ),
					esc_url( get_bloginfo( 'url' ) )
				)
			)
		);

	}

	/**
	 * Allow Feeds
	 *
	 * @param   boolean $bool  Allow feeds.
	 * @return  boolean         True/false.
	 */
	public function allow_feeds( $bool ) {

		if ( is_feed() && (bool) get_option( 'password_protected_feeds' ) ) {
			return 0;
		}

		return $bool;

	}

	/**
	 * Allow Administrators
	 *
	 * @param   boolean $bool  Allow administrators.
	 * @return  boolean         True/false.
	 */
	public function allow_administrators( $bool ) {

		if ( ! is_admin() && current_user_can( 'manage_options' ) && (bool) get_option( 'password_protected_administrators' ) ) {
			return 0;
		}

		return $bool;

	}

	/**
	 * Allow Users
	 *
	 * @param   boolean $bool  Allow administrators.
	 * @return  boolean         True/false.
	 */
	public function allow_users( $bool ) {

		if ( ! is_admin() && is_user_logged_in() && (bool) get_option( 'password_protected_users' ) ) {
			return 0;
		}

		return $bool;

	}

	/**
	 * Allow IP Addresses
	 *
	 * If user has a valid email address, return false to disable password protection.
	 *
	 * @param   boolean $bool  Allow IP addresses.
	 * @return  boolean         True/false.
	 */
	public function allow_ip_addresses( $bool ) {

		$ip_addresses = $this->get_allowed_ip_addresses();

		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( $remote_addr && in_array( $remote_addr, $ip_addresses, true ) ) {
			$bool = false;
		} else {
			$bool = apply_filters( 'password_protected__allowed_ip_ranges', $bool, $ip_addresses, $remote_addr );
		}

		return $bool;

	}


	/**
	 * Is protection active.
	 *
	 * @param bool $is_active is active {true|false}.
	 *
	 * @return bool
	 */
	public function elementor_compatibility( $is_active  ) {
		if ( class_exists( '\\Elementor\\plugin' ) ) {
			if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				$is_active = false;
			}
		}
		return $is_active;
	}

	/**
	 * Get Allowed IP Addresses
	 *
	 * @return  array  IP addresses.
	 */
	public function get_allowed_ip_addresses() {
		$allowed_ip_address = get_option( 'password_protected_allowed_ip_addresses' );
		if ( empty( $allowed_ip_address ) ) {
			return array();
		}
		return explode( "\n", $allowed_ip_address );

	}

	/**
	 * Allow the remember me function
	 *
	 * @return. boolean
	 */
	public function allow_remember_me() {

		return (bool) get_option( 'password_protected_remember_me' );

	}

	/**
	 * Hash Master Password (SEC-004)
	 *
	 * Hashes the site master password using WordPress's modern password hashing
	 * (bcrypt by default, argon2 if available). Used for the master password
	 * stored in the `password_protected_password` option.
	 *
	 * @param  string $password  Plaintext password.
	 * @return string             Hashed password (bcrypt/argon2).
	 */
	public function hash_master_password( $password ) {

		return wp_hash_password( $password );

	}

	/**
	 * Verify Master Password (SEC-004)
	 *
	 * Verifies a submitted password against the stored master-password hash.
	 * Uses wp_check_password() which automatically handles legacy MD5 hashes
	 * (32-char hex) and modern bcrypt/argon2 hashes. After a successful match
	 * against a legacy MD5 hash, the caller should rehash with
	 * hash_master_password() and update the option.
	 *
	 * @param  string $password  Submitted plaintext password.
	 * @param  string $stored    Stored hash (MD5 or bcrypt/argon2).
	 * @return bool               True if password matches.
	 */
	public function verify_master_password( $password, $stored ) {

		if ( empty( $stored ) ) {
			return false;
		}

		return wp_check_password( $password, $stored );
	}

	/**
	 * Is Stored Hash Legacy MD5 (SEC-004)
	 *
	 * Detects whether the stored master-password hash is a legacy unsalted
	 * MD5 (32-char lowercase hex). Used to trigger a one-time upgrade to
	 * bcrypt/argon2 on the next successful login.
	 *
	 * @param  string $stored  Stored hash.
	 * @return bool             True if hash is legacy MD5.
	 */
	public function is_legacy_md5_hash( $stored ) {

		return is_string( $stored ) && (bool) preg_match( '/^[a-f0-9]{32}$/', $stored );

	}

	/**
	 * Maybe Process Logout
	 */
	public function maybe_process_logout() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['password-protected'] ) && 'logout' === sanitize_text_field( wp_unslash( $_REQUEST['password-protected'] ) ) ) {

			$this->logout();

			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = remove_query_arg( 'password-protected', esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ), array( 'http', 'https' ) ) );
			} else {
				$redirect_to = home_url( '/' );
			}

			$this->safe_redirect( $redirect_to );
			exit();

		}
		// phpcs:enable

	}

	/**
	 * Maybe Process Login
	 */
	public function maybe_process_login() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( $this->is_active() && isset( $_REQUEST['password_protected_pwd'] ) ) {

			$password_protected_pwd 	= sanitize_text_field( wp_unslash( $_REQUEST['password_protected_pwd'] ) );
			$default_password         	= get_option( 'password_protected_password' );

			$auth 						= false;
			$p_id                       = 0;

			if ( empty( $default_password ) ) {

				$authentication = $this->password_protected_check_additional_passwords( $password_protected_pwd );
				$auth = $authentication['auth'];
				$p_id = $authentication['p_id'];

			} else {

				// SEC-004: verify the master password using wp_check_password() so
				// both legacy MD5 and modern bcrypt/argon2 hashes are accepted.
				$master_match = $this->verify_master_password( $password_protected_pwd, $default_password );

				if ( $master_match || apply_filters( 'password_protected_process_login', false, $password_protected_pwd ) ) {
					$auth = true;

					// SEC-004: one-time upgrade from legacy MD5 to bcrypt/argon2.
					if ( $master_match && $this->is_legacy_md5_hash( $default_password ) ) {
						$new_hash = $this->hash_master_password( $password_protected_pwd );
						update_option( 'password_protected_password', $new_hash );
					}
				}

				if ( ! $auth ) {

					$authentication = $this->password_protected_check_additional_passwords( $password_protected_pwd );
					$auth = $authentication['auth'];
					$p_id = $authentication['p_id'];
				}

			}

			$this->errors 				= apply_filters( 'password_protected_verify_recaptcha', $this->errors );

			if( count( @$this->errors->errors ) > 0 ) return;

			$this->password_protected_process_login( $auth, $password_protected_pwd, $p_id );

		}
		// phpcs:enable

	}

	public function password_protected_process_login( $auth, $requested_password, $password_id ) {

		// Backport COMPAT-001: scalar type hint removed for PHP 5.6 compatibility.
		if ( ! is_bool( $auth ) ) {
			$auth = (bool) $auth;
		}

		if( $auth )
			$throttle = apply_filters( 'password_protected_check_for_throttling', true );


		if( $auth && $throttle ) {
			
			do_action( 'password_protected_success_login_attempt', 'global', $requested_password, $password_id );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public password login form.
			$remember = isset( $_REQUEST['password_protected_rememberme'] ) ? boolval( wp_unslash( $_REQUEST['password_protected_rememberme'] ) ) : false;

			if ( ! $this->allow_remember_me() ) {
				$remember = false;
			}
			$this->set_auth_cookie( $remember );
			
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public password login form.
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

			$redirect_to = apply_filters( 'password_protected_login_redirect', $redirect_to, $requested_password );

			if ( ! empty( $redirect_to ) ) {
				$this->safe_redirect( remove_query_arg( 'password-protected', $redirect_to ) );
				exit;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public password login form.
			} elseif ( isset( $_GET['password_protected_pwd'] ) ) {
				$this->safe_redirect( remove_query_arg( 'password-protected' ) );
				exit;
			} else {
				$this->safe_redirect( site_url() );
				exit;
			}
		} else {
			do_action( 'password_protected_failure_login_attempt', 'global', $requested_password, $password_id );

			// ... otherwise incorrect password
			$this->clear_auth_cookie();
			
			$show_default_error = apply_filters( 'password_protected_throttling_error_messages', true );
			
			if( $show_default_error )
				$this->errors->add( 'incorrect_password', __( 'Incorrect Password', 'password-protected' ) );
		}
	}
	
	/**
	 * Check additional passwords via the password_protected_authenticate_additional filter.
	 *
	 * @param  mixed $requested_password
	 * @return array{ auth: bool, p_id: int }
	 */
	public function password_protected_check_additional_passwords( $requested_password ) {

		$result = apply_filters(
			'password_protected_authenticate_additional',
			array(
				'auth'        => false,
				'password_id' => 0,
			),
			$requested_password,
			$this->errors
		);

		if ( ! is_array( $result ) ) {
			return array(
				'auth' => false,
				'p_id' => 0,
			);
		}

		$auth  = ! empty( $result['auth'] );
		$p_id  = isset( $result['password_id'] ) ? (int) $result['password_id'] : 0;

		if ( ! $auth && apply_filters( 'password_protected_process_login', false, $requested_password ) ) {
			$auth = true;
		}

		return array(
			'auth' => $auth,
			'p_id' => $p_id,
		);
	}

	/**
	 * Is User Logged In?
	 *
	 * @return  boolean
	 */
	public function is_user_logged_in() {

		return $this->is_active() && $this->validate_auth_cookie();

	}

	/**
	 * Maybe Show Login
	 */
	public function maybe_show_login() {

		if ( class_exists( 'Login_designer' ) || apply_filters( 'password_protected_allow_customize_preview', false ) ) {
			if ( is_customize_preview() ) {
				return 1;
			}
		}

		// Filter for adding exceptions.
		$show_login = apply_filters( 'password_protected_show_login', $this->is_active() );
		
		// Logged in
		if ( $this->is_user_logged_in() ) {
			$show_login = false;
		}
		
		if ( ! $show_login ) {
			return 1;
		}

		// Show login form
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public password login flow.
		if ( isset( $_REQUEST['password-protected'] ) && 'login' === sanitize_text_field( wp_unslash( $_REQUEST['password-protected'] ) ) ) {

			$default_theme_file = locate_template( array( 'password-protected-login.php' ) );

			if ( empty( $default_theme_file ) ) {
				$default_theme_file = dirname( __FILE__ ) . '/theme/password-protected-login.php';
			}

			$theme_file = apply_filters( 'password_protected_theme_file', $default_theme_file );
			if ( ! file_exists( $theme_file ) ) {
				$theme_file = $default_theme_file;
			}

			load_template( $theme_file );
			exit();

		} else {
            global $wp;

			$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
			$redirect_to  = add_query_arg( 'password-protected', 'login', home_url( $wp->request . ( $query_string ? '?' . $query_string : '' ) ) );
			$redirect_to = pp__add_dynamic_arg( $redirect_to );

			// URL to redirect back to after login
			$redirect_to_url = '';
			if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$redirect_to_url = esc_url_raw(
					( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . wp_unslash( $_SERVER['REQUEST_URI'] )
				);
			}
			$redirect_to_url = apply_filters( 'password_protected_login_redirect_url', $redirect_to_url );
			$redirect_to_url = pp__add_dynamic_arg( $redirect_to_url );
			if ( ! empty( $redirect_to_url ) ) {
				$redirect_to = add_query_arg( 'redirect_to', urlencode( $redirect_to_url ), $redirect_to );
			}

			nocache_headers();
			$this->safe_redirect( $redirect_to );
			exit();

		}
	}

	/**
	 * Get Site ID
	 *
	 * @return  string  Site ID.
	 */
	public function get_site_id() {

		global $blog_id;
		return 'bid_' . apply_filters( 'password_protected_blog_id', $blog_id );

	}

	/**
	 * Login URL
	 *
	 * @return  string  Login URL.
	 */
	public function login_url() {
        global $wp;
		$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		return add_query_arg( 'password-protected', 'login', home_url( $wp->request . ( $query_string ? '?' . $query_string : '' ) ) );

	}

	/**
	 * Logout
	 */
	public function logout() {

		$this->clear_auth_cookie();
		do_action( 'password_protected_logout' );

	}

	/**
	 * Logout URL
	 *
	 * @param   string $redirect_to  Optional. Redirect URL.
	 * @return  string                Logout URL.
	 */
	public function logout_url( $redirect_to = '' ) {

		$query = array(
			'password-protected' => 'logout',
			'redirect_to'        => esc_url_raw( $redirect_to ),
		);

		if ( empty( $query['redirect_to'] ) ) {
			unset( $query['redirect_to'] );
		}

		return add_query_arg( $query, home_url() );

	}

	/**
	 * Logout Link
	 *
	 * @param   array $args  Link args.
	 * @return  string         HTML link tag.
	 */
	public function logout_link( $args = null ) {

		// Only show if user is logged in
		if ( ! $this->is_user_logged_in() ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'redirect_to' => '',
				'text'        => __( 'Logout', 'password-protected' ),
			)
		);

		if ( empty( $args['text'] ) ) {
			$args['text'] = __( 'Logout', 'password-protected' );
		}

		return sprintf( '<a href="%s">%s</a>', esc_url( $this->logout_url( $args['redirect_to'] ) ), esc_html( $args['text'] ) );

	}

	/**
	 * Logout Link Shortcode
	 *
	 * @param   array $args  Link args.
	 * @return  string         HTML link tag.
	 */
	public function logout_link_shortcode( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'redirect_to' => '',
				'text'        => $content,
			),
			$atts,
			'logout_link_shortcode'
		);

		return $this->logout_link( $atts );

	}

	/**
	 * Get Hashed Password
	 *
	 * @return  string  Hashed password.
	 */
	public function get_hashed_password() {

		return md5( get_option( 'password_protected_password' ) . wp_salt() );

	}

	/**
	 * Validate Auth Cookie
	 *
	 * @param   string $cookie  Cookie string.
	 * @param   string $scheme  Cookie scheme.
	 * @return  boolean           Validation successful?
	 */
	public function validate_auth_cookie( $cookie = '', $scheme = '', $hashed_password = '' ) {

		if ( ! $cookie_elements = $this->parse_auth_cookie( $cookie, $scheme ) ) {
			do_action( 'password_protected_auth_cookie_malformed', $cookie, $scheme );
			return false;
		}

		$site_id    = isset( $cookie_elements['site_id'] ) ? $cookie_elements['site_id'] : '';
		$expiration = isset( $cookie_elements['expiration'] ) ? $cookie_elements['expiration'] : 0;
		$hmac       = isset( $cookie_elements['hmac'] ) ? $cookie_elements['hmac'] : '';
		$scheme     = isset( $cookie_elements['scheme'] ) ? $cookie_elements['scheme'] : $scheme;

		$expired = $expiration;

		// Allow a grace period for POST and AJAX requests
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( defined( 'DOING_AJAX' ) || 'POST' === $request_method ) {
			$expired += 3600;
		}

		// Quick check to see if an honest cookie has expired
		if ( $expired < current_time( 'timestamp' ) ) {
			do_action( 'password_protected_auth_cookie_expired', $cookie_elements );
			return false;
		}

		if ( empty( $hashed_password ) ) {
			$hashed_password = $this->get_hashed_password();
		}
		$key  = md5( $this->get_site_id() . $hashed_password . '|' . $expiration ); // need to modify
		$hash = hash_hmac( 'md5', $this->get_site_id() . '|' . $expiration, $key );

		if ( $hmac != $hash ) {
			do_action( 'password_protected_auth_cookie_bad_hash', $cookie_elements );
			return false;
		}

		if ( $expiration < current_time( 'timestamp' ) ) { // AJAX/POST grace period set above
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress auth cookie grace period global.
			$GLOBALS['login_grace_period'] = 1;
		}

		return true;

	}

	/**
	 * Generate Auth Cookie
	 *
	 * @param   int    $expiration  Expiration time in seconds.
	 * @param   string $scheme      Cookie scheme.
	 * @return  string               Cookie.
	 */
	public function generate_auth_cookie( $expiration, $scheme = 'auth', $hashed_password = '' ) {

		if ( empty( $hashed_password ) ) {
			$hashed_password = $this->get_hashed_password();
		}
		$key    = md5( $this->get_site_id() . $hashed_password . '|' . $expiration ); // need to modify
		$hash   = hash_hmac( 'md5', $this->get_site_id() . '|' . $expiration, $key );
		$cookie = $this->get_site_id() . '|' . $expiration . '|' . $hash;

		return $cookie;

	}

	/**
	 * Parse Auth Cookie
	 *
	 * @param   string $cookie  Cookie string.
	 * @param   string $scheme  Cookie scheme.
	 * @return  string           Cookie string.
	 */
	public function parse_auth_cookie( $cookie = '', $scheme = '' ) {
		if ( empty( $cookie ) ) {

			$cookie_name = $this->cookie_name();
			$use_transient = get_option( 'password_protected_use_transient', 'default' );

			$cookie = password_protected_cookie( 'get', array( 'name' => $cookie_name ) );

			if ( empty( $cookie ) ) {
				return false;
			}
		}

		$cookie_elements = explode( '|', $cookie );

		if ( count( $cookie_elements ) != 3 ) {
			return false;
		}

		list( $site_id, $expiration, $hmac ) = $cookie_elements;

		return compact( 'site_id', 'expiration', 'hmac', 'scheme' );

	}

	/**
	 * Set Auth Cookie
	 *
	 * @todo
	 *
	 * @param  boolean $remember  Remember logged in.
	 * @param  string  $secure    Secure cookie.
	 */
	public function set_auth_cookie( $remember = false, $secure = '' ) {

		if ( $remember ) {
			$expiration_time = apply_filters( 'password_protected_auth_cookie_expiration', get_option( 'password_protected_remember_me_lifetime', 14 ) * DAY_IN_SECONDS, $remember );
			$expiration      = $expire = current_time( 'timestamp' ) + $expiration_time;
		} else {
			$expiration_time = apply_filters( 'password_protected_auth_cookie_expiration', DAY_IN_SECONDS * 20, $remember );
			$expiration      = current_time( 'timestamp' ) + $expiration_time;
			$expire          = 0;
		}

		if ( '' === $secure ) {
			$secure = is_ssl();
		}

		// SEC-004: default the Secure flag to the computed is_ssl() value so HTTPS
		// cookies are marked Secure out of the box. Filters can still override.
		$secure_password_protected_cookie = apply_filters( 'password_protected_secure_password_protected_cookie', $secure, $secure );
		$password_protected_cookie        = $this->generate_auth_cookie( $expiration, 'password_protected' );

		$use_transient = get_option( 'password_protected_use_transient', 'default' );
		
		
		password_protected_cookie(
			'set',
			array(
				'name' => $this->cookie_name(),
				'data' => $password_protected_cookie,
				'secure' => $secure_password_protected_cookie,
				'expire' => $expire,
			)
		);

	}

	/**
	 * Clear Auth Cookie
	 */
	public function clear_auth_cookie() {
		$use_transient = get_option( 'password_protected_use_transient', 'default' );
		password_protected_cookie( 'delete', array( 'name' => $this->cookie_name() ) );
	}

	/**
	 * Cookie Name
	 *
	 * @return  string  Cookie name.
	 */
	public function cookie_name() {

		/**
		 * Filters the cookie name
		 */
		return apply_filters( 'password_protected_cookie_name', $this->get_site_id() . '_password_protected_auth', $this );

	}

	/**
	 * Install
	 */
	public function install() {

		$old_version = get_option( 'password_protected_version' );

		// 1.1 - Upgrade to MD5
		if ( empty( $old_version ) || $old_version == '1.1' ) {
			$pwd = get_option( 'password_protected_password' );
			if ( ! empty( $pwd ) ) {
				$new_pwd = md5( $pwd );
				update_option( 'password_protected_password', $new_pwd );
			}
		}

		update_option( 'password_protected_version', $this->version );

	}

	/**
	 * Deactivate
	 *
	 * Clears scheduled cron events on deactivation so the weekly activity
	 * report email does not keep firing after the plugin is deactivated.
	 */
	public function deactivate() {

		wp_clear_scheduled_hook( 'password_protected_email_notification_hook' );

	}

	/**
	 * Compat
	 *
	 * Support for 3rd party plugins:
	 *
	 * - Login Logo       https://wordpress.org/plugins/login-logo/
	 * - Uber Login Logo  https://wordpress.org/plugins/uber-login-logo/
	 */
	public function compat() {

		if ( class_exists( 'CWS_Login_Logo_Plugin' ) ) {

			// Add support for Mark Jaquith's Login Logo plugin
			add_action( 'password_protected_login_head', array( new CWS_Login_Logo_Plugin(), 'login_head' ) );

		} elseif ( class_exists( 'UberLoginLogo' ) ) {

			// Add support for Uber Login Logo plugin
			add_action( 'password_protected_login_head', array( 'UberLoginLogo', 'replaceLoginLogo' ) );

		}

	}

	/**
	 * Login Messages
	 * Outputs messages and errors in the login template.
	 */
	public function login_messages() {

		// Add message
		$message = apply_filters( 'password_protected_login_message', '' );
		if ( ! empty( $message ) ) {
			echo wp_kses_post( $message ) . "\n";
		}

		if ( $this->errors->get_error_code() ) {

			$errors   = '';
			$messages = '';

			foreach ( $this->errors->get_error_codes() as $code ) {
				$severity = $this->errors->get_error_data( $code );
				foreach ( $this->errors->get_error_messages( $code ) as $error ) {
					if ( 'message' == $severity ) {
						$messages .= $error . '<br />';
					} else {
						$errors .= $error . '<br />';
					}
				}
			}

			if ( ! empty( $errors ) ) {
				echo '<div id="login_error" class="notice notice-error">' . wp_kses_post( apply_filters( 'password_protected_login_errors', $errors ) ) . "</div>\n";
			}
			if ( ! empty( $messages ) ) {
				echo '<p class="message">' . wp_kses_post( apply_filters( 'password_protected_login_messages', $messages ) ) . "</p>\n";
			}
		}

	}

	/**
	 * Load Theme Stylesheet
	 *
	 * Check wether a 'password-protected-login.css' stylesheet exists in your theme
	 * and if so loads it.
	 *
	 * Works with child themes.
	 *
	 * Possible to specify a different file in the theme folder via the
	 * 'password_protected_stylesheet_file' filter (allows for theme subfolders).
	 */
	public function load_theme_stylesheet() {

		$filename = apply_filters( 'password_protected_stylesheet_file', 'password-protected-login.css' );

		$located = locate_template( $filename );

		if ( ! empty( $located ) ) {

			$stylesheet_directory = trailingslashit( get_stylesheet_directory() );
			$template_directory   = trailingslashit( get_template_directory() );
			$style_version        = (string) filemtime( $located );

			if ( $stylesheet_directory == substr( $located, 0, strlen( $stylesheet_directory ) ) ) {
				wp_enqueue_style( 'password-protected-login', get_stylesheet_directory_uri() . '/' . $filename, array(), $style_version );
			} elseif ( $template_directory == substr( $located, 0, strlen( $template_directory ) ) ) {
				wp_enqueue_style( 'password-protected-login', get_template_directory_uri() . '/' . $filename, array(), $style_version );
			}
		}

	}

	/**
	 * Safe Redirect
	 *
	 * Ensure the redirect is to the same site or pluggable list of allowed domains.
	 * If invalid will redirect to ...
	 * Based on the WordPress wp_safe_redirect() function.
	 */
	public function safe_redirect( $location, $status = 302 ) {

		$location = wp_sanitize_redirect( $location );
		$location = wp_validate_redirect( $location, home_url() );

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Validated redirect target.
		wp_redirect( $location, $status );

	}

	/**
	 * Is Plugin Supported?
	 *
	 * Check to see if there are any known reasons why this plugin may not work in
	 * the user's hosting environment.
	 *
	 * @return  boolean
	 */
	static function is_plugin_supported() {

		return true;

	}

	/**
	 * Check whether a given request has permissions
	 *
	 * Always allow logged in users who require REST API for Gutenberg
	 * and other admin/plugin compatibility.
	 *
	 * @param   WP_REST_Request $access  Full details about the request.
	 * @return  WP_Error|boolean
	 */
	public function only_allow_logged_in_rest_access( $access ) {

        if ( $this->is_active() && ! $this->is_user_logged_in() && ! is_user_logged_in() && ! (bool) get_option( 'password_protected_rest' ) ) {
            return new WP_Error( 'rest_cannot_access', __( 'Only authenticated users can access the REST API.', 'password-protected' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return $access;
	}

	/**
	 * Print text above password field
	 * @return  void.
	 */
	public function password_protected_above_password_field() {
		$text = get_option('password_protected_text_above_password');
		if( ! empty( $text ) ) {
			echo '<div class="password-protected-text-above" style="width:100%;">' . wp_kses_post( $text ) . '</div>';
		}
	}

	/**
	 * Print text below password field
	 * @return  void.
	 */
	public function password_protected_below_password_field() {
		$text = get_option('password_protected_text_below_password');
		if( ! empty( $text ) ) {
			echo '<div class="password-protected-text-below" style="width:100%">' . wp_kses_post( $text ) . '</div>';
		}
	}

}


