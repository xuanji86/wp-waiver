<?php
/**
 * Built-in anti-bot layers: HMAC time-trap token and per-IP rate limiting.
 * (The honeypot lives in the form template + submit handler.)
 * Bot verdicts are handled upstream with a fake success — no feedback signal.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Issue a signed timestamp token embedded in the form.
 */
function wpw_timetrap_token( $doc_id ) {
	$ts = time();
	return $ts . '|' . wp_hash( 'wpw-tt|' . $ts . '|' . (int) $doc_id );
}

/**
 * Validate the token: authentic, at least N seconds old (a human has to read
 * the agreement and draw a signature), and no older than a day (anti-replay).
 */
function wpw_timetrap_ok( $token, $doc_id ) {
	$parts = explode( '|', (string) $token, 2 );
	if ( 2 !== count( $parts ) ) {
		return false;
	}
	$ts  = (int) $parts[0];
	$mac = $parts[1];
	if ( ! hash_equals( wp_hash( 'wpw-tt|' . $ts . '|' . (int) $doc_id ), $mac ) ) {
		return false;
	}
	$age = time() - $ts;
	$min = (int) apply_filters( 'wpw_min_fill_seconds', 8 );
	return $age >= $min && $age <= DAY_IN_SECONDS;
}

/**
 * Sliding per-IP limit: counts this submission and reports whether it is
 * still within the hourly budget.
 */
function wpw_rate_limit_ok( $ip ) {
	$key   = 'wpw_rl_' . md5( (string) $ip );
	$count = (int) get_transient( $key );
	$max   = (int) apply_filters( 'wpw_max_submissions_per_hour', 5 );
	if ( $count >= $max ) {
		return false;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}
