<?php
/**
 * Transient functions.
 *
 * @usage Transient functions to get, set and delete transient values
 * @description Use transient instead of cookies.
 * @package Password Protected
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

if ( ! function_exists( 'pp_resolve_client_ip' ) ) {
	/**
	 * Canonical client-IP resolver.
	 *
	 * Uses REMOTE_ADDR only. Forwarded headers (X-Forwarded-For, Client-IP,
	 * etc.) are intentionally NOT consulted — they are client-supplied and
	 * trivially spoofable, which was the root cause of SEC-003. Sites behind a
	 * reverse proxy should configure the proxy to overwrite REMOTE_ADDR at the
	 * web-server level (e.g. mod_remoteip, ngx_http_realip_module, or the
	 * proxy's "restore visitor IP" setting) rather than relying on PHP to
	 * interpret forwarded headers.
	 *
	 * Always returns a string — falls back to '0.0.0.0' rather than null so
	 * that callers never collapse distinct clients into one empty identifier.
	 *
	 * @return string Client IP address (never null/empty).
	 */
	function pp_resolve_client_ip() {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
			$remote_addr = '';
		}

		if ( ! empty( $remote_addr ) ) {
			return $remote_addr;
		}
		return '0.0.0.0';
	}
}

if ( ! function_exists( 'pp_get_transient_identifier' ) ) {
	/**
	 * Get identifier.
	 *
	 * Returns a per-client identifier for the transient key. Uses the canonical
	 * client-IP resolver, which always returns a non-empty string. When the
	 * resolved IP is the fallback sentinel '0.0.0.0' (no usable identifier
	 * could be determined), this function fails closed by returning a random
	 * per-client key stored in a signed cookie — distinct clients never share
	 * a transient bucket.
	 *
	 * @param string $key transient key.
	 * @return string transient identifier.
	 */
	function pp_get_transient_identifier( $key = '' ) {
		$ip = pp_resolve_client_ip();

		if ( '0.0.0.0' === $ip ) {
			// Fail closed: use a per-client random credential stored in a cookie.
			$cookie_name = 'pp_session_id';
			$session_id  = '';
			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				$session_id = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
			}
			if ( empty( $session_id ) || ! preg_match( '/^[a-f0-9]{64}$/', $session_id ) ) {
				$session_id = hash( 'sha256', wp_generate_password( 32, true ) . wp_salt() . uniqid( '', true ) );
				$secure     = is_ssl();
				setcookie( $cookie_name, $session_id, current_time( 'timestamp' ) + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
				if ( COOKIEPATH != SITECOOKIEPATH ) {
					setcookie( $cookie_name, $session_id, current_time( 'timestamp' ) + DAY_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true );
				}
			}
			return $key . '_session_' . $session_id;
		}

		return $key . '_' . $ip;
	}
}

if ( ! function_exists( 'pp_get_ip_address' ) ) {
	/**
	 * Get IP Address (back-compat shim).
	 *
	 * Delegates to pp_resolve_client_ip(). Kept for backward compatibility with
	 * any code (including the Pro add-on) that calls pp_get_ip_address().
	 *
	 * @return string Client IP address (never null/empty).
	 */
	function pp_get_ip_address() {
		return pp_resolve_client_ip();
	}
}

if ( ! function_exists( 'pp_get_transient' ) ) {
	/**
	 * Get Transient
	 *
	 * @param string $key transient key.
	 * @return bool|mixed
	 */
	function pp_get_transient( $key = '' ) {
		$transient = pp_get_transient_identifier( $key );
		$value = get_transient( $transient );
		if ( ! empty( $value ) ) {
			return $value;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'pp_set_transient' ) ) {
	/**
	 * Set Transient
	 *
	 * @param string $key      transient key.
	 * @param string $value    transient value.
	 * @param int    $duration transient duration.
	 */
	function pp_set_transient( $key = '', $value = '', $duration = HOUR_IN_SECONDS ) {
		$transient = pp_get_transient_identifier( $key );
		set_transient( $transient, $value, $duration );
	}
}

if ( ! function_exists( 'pp_delete_transient' ) ) {
	/**
	 * Delete transient
	 *
	 * @param string $key transient key.
	 */
	function pp_delete_transient( $key = '' ) {
		$transient = pp_get_transient_identifier( $key );
		delete_transient( $transient );
	}
}
// phpcs:enable
