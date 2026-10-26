<?php
/**
 * Deactivate incompatible paid extension versions when Free meets the gate version.
 *
 * @package Password_Protected
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Password_Protected_Extension_Compat' ) ) {

	/**
	 * Extension compatibility gate (free plugin only).
	 */
	class Password_Protected_Extension_Compat {

		const PASSWORD_PROTECTED_EXTENSION_GATE_MIN_FREE = '2.8.5';

		const PASSWORD_PROTECTED_EXTENSION_MIN_VERSION = '2.4.0';

		const PASSWORD_PROTECTED_EXTENSION_BASENAME = 'password-protected-pro/password-protected-pro.php';

		const PASSWORD_PROTECTED_EXTENSION_COMPAT_NOTICE_OPTION = 'password_protected_extension_compat_notice';

		/**
		 * Bootstrap hooks.
		 */
		public static function init() {
			add_action( 'plugins_loaded', array( __CLASS__, 'maybe_deactivate_incompatible_extension' ), 20 );
			add_action( 'activated_plugin', array( __CLASS__, 'on_activated_plugin' ), 10, 1 );
			add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrader_complete' ), 10, 2 );

			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'handle_dismiss_notice' ), 10 );
				add_action( 'admin_notices', array( __CLASS__, 'render_extension_compat_admin_notice' ), 10 );
				add_action( 'network_admin_notices', array( __CLASS__, 'render_extension_compat_admin_notice' ), 10 );
				add_action( 'admin_footer', array( __CLASS__, 'wire_dismissible_notice_script' ) );
			}
		}

		/**
		 * Re-check after another plugin is activated.
		 *
		 * @param string $plugin Plugin basename.
		 */
		public static function on_activated_plugin( $plugin ) {
			if ( self::PASSWORD_PROTECTED_EXTENSION_BASENAME === $plugin ) {
				self::maybe_deactivate_incompatible_extension();
			}
		}

		/**
		 * Re-check after Free is updated.
		 *
		 * @param WP_Upgrader $upgrader Upgrader instance.
		 * @param array       $context  Upgrade context.
		 */
		public static function on_upgrader_complete( $upgrader, $context ) {
			if ( ! is_array( $context ) ) {
				return;
			}

			if ( ! isset( $context['action'], $context['type'], $context['plugins'] ) ) {
				return;
			}

			if ( 'update' !== $context['action'] || 'plugin' !== $context['type'] ) {
				return;
			}

			if ( ! is_array( $context['plugins'] ) ) {
				return;
			}

			$free_basename = plugin_basename( PASSWORD_PROTECTED_DIR . 'password-protected.php' );

			foreach ( $context['plugins'] as $updated_plugin ) {
				if ( $free_basename === $updated_plugin ) {
					self::maybe_deactivate_incompatible_extension();
					break;
				}
			}
		}

		/**
		 * Deactivate old extension builds when Free gate version is satisfied.
		 */
		public static function maybe_deactivate_incompatible_extension() {
			$free_version = self::get_free_version();

			if ( null === $free_version ) {
				return;
			}

			if ( version_compare( $free_version, self::PASSWORD_PROTECTED_EXTENSION_GATE_MIN_FREE, '<' ) ) {
				return;
			}

			$main_file = WP_PLUGIN_DIR . '/' . self::PASSWORD_PROTECTED_EXTENSION_BASENAME;

			if ( ! file_exists( $main_file ) ) {
				self::clear_notice_option();
				return;
			}

			$extension_version = self::get_extension_version();

			if ( null === $extension_version ) {
				return;
			}

			if ( ! self::is_extension_active() ) {
				if ( version_compare( $extension_version, self::PASSWORD_PROTECTED_EXTENSION_MIN_VERSION, '>=' ) ) {
					self::clear_notice_option();
				}
				return;
			}

			if ( version_compare( $extension_version, self::PASSWORD_PROTECTED_EXTENSION_MIN_VERSION, '>=' ) ) {
				self::clear_notice_option();
				return;
			}

			deactivate_plugins( self::PASSWORD_PROTECTED_EXTENSION_BASENAME, true );

			update_option(
				self::PASSWORD_PROTECTED_EXTENSION_COMPAT_NOTICE_OPTION,
				array(
					'deactivated_at'    => time(),
					'extension_version' => $extension_version,
					'free_version'      => $free_version,
				),
				false
			);
		}

		/**
		 * Admin notice after incompatible extension was deactivated.
		 */
		public static function render_extension_compat_admin_notice() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$notice_data = get_option( self::PASSWORD_PROTECTED_EXTENSION_COMPAT_NOTICE_OPTION );

			if ( empty( $notice_data ) || ! is_array( $notice_data ) ) {
				return;
			}

			$dismiss_url = wp_nonce_url(
				add_query_arg( 'pp-dismiss-extension-compat', '1' ),
				'pp_dismiss_extension_compat'
			);

			$message = esc_html__(
				'For compatibility reasons, your Pro plugin has been temporarily deactivated. Please update the Pro plugin to the latest version and reactivate it to continue using Pro features.',
				'password-protected'
			);

			printf(
				'<div class="notice notice-error is-dismissible password-protected-extension-compat-notice" data-dismiss-url="%1$s"><p>%2$s</p><p><a href="%3$s">%4$s</a></p></div>',
				esc_url( $dismiss_url ),
				esc_html( $message ),
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Go to Plugins', 'password-protected' )
			);
		}

		/**
		 * Map core dismiss control to our dismiss endpoint.
		 */
		public static function wire_dismissible_notice_script() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ( empty( get_option( self::PASSWORD_PROTECTED_EXTENSION_COMPAT_NOTICE_OPTION ) ) ) {
				return;
			}

			?>
			<script>
			( function () {
				var notice = document.querySelector( '.password-protected-extension-compat-notice' );
				if ( ! notice ) {
					return;
				}
				var dismissUrl = notice.getAttribute( 'data-dismiss-url' );
				var button = notice.querySelector( '.notice-dismiss' );
				if ( button && dismissUrl ) {
					button.addEventListener( 'click', function () {
						window.location.href = dismissUrl;
					} );
				}
			} )();
			</script>
			<?php
		}

		/**
		 * Dismiss admin notice.
		 */
		public static function handle_dismiss_notice() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below.
			if ( ! isset( $_GET['pp-dismiss-extension-compat'] ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified with wp_verify_nonce.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'pp_dismiss_extension_compat' ) ) {
				return;
			}

			self::clear_notice_option();

			$redirect = wp_get_referer();
			if ( ! $redirect ) {
				$redirect = admin_url( 'plugins.php' );
			}

			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Whether the extension plugin is active (site or network).
		 *
		 * @return bool
		 */
		private static function is_extension_active() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active( self::PASSWORD_PROTECTED_EXTENSION_BASENAME ) ) {
				return true;
			}

			if ( is_multisite() && function_exists( 'is_plugin_active_for_network' ) ) {
				return is_plugin_active_for_network( self::PASSWORD_PROTECTED_EXTENSION_BASENAME );
			}

			return false;
		}

		/**
		 * Read free plugin version from main file header.
		 *
		 * @return string|null
		 */
		private static function get_free_version() {
			$main_file = PASSWORD_PROTECTED_DIR . 'password-protected.php';

			if ( ! file_exists( $main_file ) ) {
				return null;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$header = get_plugin_data( $main_file, false, false );
			$version = isset( $header['Version'] ) ? $header['Version'] : '';

			if ( '' === $version ) {
				return null;
			}

			return $version;
		}

		/**
		 * Read extension version from plugin header.
		 *
		 * @return string|null
		 */
		private static function get_extension_version() {
			$main_file = WP_PLUGIN_DIR . '/' . self::PASSWORD_PROTECTED_EXTENSION_BASENAME;

			if ( ! file_exists( $main_file ) ) {
				return null;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$header = get_plugin_data( $main_file, false, false );
			$version = isset( $header['Version'] ) ? $header['Version'] : '';

			if ( '' === $version ) {
				return null;
			}

			/**
			 * Filter extension version used for compatibility checks (tests).
			 *
			 * @param string $version Version string from plugin header.
			 */
			return apply_filters( 'password_protected_extension_compat_version', $version );
		}

		/**
		 * Remove stored notice flag.
		 */
		private static function clear_notice_option() {
			delete_option( self::PASSWORD_PROTECTED_EXTENSION_COMPAT_NOTICE_OPTION );
		}
	}
}
