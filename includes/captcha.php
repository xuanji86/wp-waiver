<?php
/**
 * Optional captcha layer. Two providers with the same shape: a front-end
 * widget plus a server-side verify POST. Nothing third-party loads unless a
 * provider AND both keys are configured.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Active provider slug ('recaptcha' | 'turnstile') or '' when disabled.
 */
function wpw_captcha_provider() {
	$s = wpw_settings();
	if ( 'none' === $s['captcha_provider'] || '' === $s['captcha_site_key'] || '' === $s['captcha_secret'] ) {
		return '';
	}
	return $s['captcha_provider'];
}

function wpw_captcha_script_url() {
	switch ( wpw_captcha_provider() ) {
		case 'recaptcha':
			return 'https://www.google.com/recaptcha/api.js';
		case 'turnstile':
			return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
	}
	return '';
}

function wpw_captcha_widget_html() {
	$s = wpw_settings();
	switch ( wpw_captcha_provider() ) {
		case 'recaptcha':
			return '<div class="g-recaptcha" data-sitekey="' . esc_attr( $s['captcha_site_key'] ) . '"></div>';
		case 'turnstile':
			return '<div class="cf-turnstile" data-sitekey="' . esc_attr( $s['captcha_site_key'] ) . '"></div>';
	}
	return '';
}

/**
 * Server-side verification of the submitted challenge response.
 * Returns true when no provider is configured.
 */
function wpw_captcha_verify() {
	$provider = wpw_captcha_provider();
	if ( '' === $provider ) {
		return true;
	}
	$s     = wpw_settings();
	$field = 'recaptcha' === $provider ? 'g-recaptcha-response' : 'cf-turnstile-response';
	$url   = 'recaptcha' === $provider
		? 'https://www.google.com/recaptcha/api/siteverify'
		: 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	$response = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
	if ( '' === $response ) {
		return false;
	}

	$result = wp_remote_post( $url, array(
		'timeout' => 10,
		'body'    => array(
			'secret'   => $s['captcha_secret'],
			'response' => $response,
			'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		),
	) );
	if ( is_wp_error( $result ) ) {
		// Verification endpoint unreachable: fail open or closed is a policy
		// call; we fail closed (treat as not verified) for a legal form.
		return false;
	}
	$body = json_decode( wp_remote_retrieve_body( $result ), true );
	return is_array( $body ) && ! empty( $body['success'] );
}
