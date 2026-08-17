<?php
/**
 * Settings: notification email, default document, signer receipt, captcha
 * provider. Stored as a single wpw_settings option.
 */

defined( 'ABSPATH' ) || exit;

function wpw_settings() {
	$defaults = array(
		'notify_email'     => get_option( 'admin_email' ),
		'default_document' => 0,
		'send_receipt'     => 0,
		'captcha_provider' => 'none',
		'captcha_site_key' => '',
		'captcha_secret'   => '',
	);
	$stored = get_option( 'wpw_settings', array() );
	return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
}

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=wpw_record',
		__( 'Waiver Settings', 'wp-waiver' ),
		__( 'Settings', 'wp-waiver' ),
		'manage_options',
		'wpw-settings',
		'wpw_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'wpw_settings_group', 'wpw_settings', array(
		'sanitize_callback' => 'wpw_sanitize_settings',
	) );
} );

function wpw_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = wpw_settings();

	$out['notify_email']     = isset( $input['notify_email'] ) ? sanitize_email( $input['notify_email'] ) : '';
	$out['default_document'] = isset( $input['default_document'] ) ? absint( $input['default_document'] ) : 0;
	$out['send_receipt']     = empty( $input['send_receipt'] ) ? 0 : 1;
	$out['captcha_site_key'] = isset( $input['captcha_site_key'] ) ? sanitize_text_field( $input['captcha_site_key'] ) : '';
	$out['captcha_secret']   = isset( $input['captcha_secret'] ) ? sanitize_text_field( $input['captcha_secret'] ) : '';

	$provider                = isset( $input['captcha_provider'] ) ? $input['captcha_provider'] : 'none';
	$out['captcha_provider'] = in_array( $provider, array( 'none', 'recaptcha', 'turnstile' ), true ) ? $provider : 'none';

	return $out;
}

function wpw_render_settings_page() {
	$s    = wpw_settings();
	$docs = get_posts( array(
		'post_type'      => 'wpw_document',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Waiver Settings', 'wp-waiver' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wpw_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wpw-notify-email"><?php esc_html_e( 'Notification email', 'wp-waiver' ); ?></label></th>
					<td>
						<input name="wpw_settings[notify_email]" id="wpw-notify-email" type="email" class="regular-text" value="<?php echo esc_attr( $s['notify_email'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Where new signed waivers are sent (signature image attached).', 'wp-waiver' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpw-default-doc"><?php esc_html_e( 'Default waiver document', 'wp-waiver' ); ?></label></th>
					<td>
						<select name="wpw_settings[default_document]" id="wpw-default-doc">
							<option value="0"><?php esc_html_e( '— None —', 'wp-waiver' ); ?></option>
							<?php foreach ( $docs as $doc ) : ?>
								<option value="<?php echo esc_attr( $doc->ID ); ?>" <?php selected( (int) $s['default_document'], $doc->ID ); ?>><?php echo esc_html( $doc->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Used by [wp_waiver_form] when no id attribute is given.', 'wp-waiver' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Signer receipt', 'wp-waiver' ); ?></th>
					<td>
						<label><input name="wpw_settings[send_receipt]" type="checkbox" value="1" <?php checked( $s['send_receipt'] ); ?> /> <?php esc_html_e( 'Email the signer a receipt copy of the agreement they signed', 'wp-waiver' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpw-captcha-provider"><?php esc_html_e( 'Captcha provider', 'wp-waiver' ); ?></label></th>
					<td>
						<select name="wpw_settings[captcha_provider]" id="wpw-captcha-provider">
							<option value="none" <?php selected( $s['captcha_provider'], 'none' ); ?>><?php esc_html_e( 'None (built-in anti-bot only)', 'wp-waiver' ); ?></option>
							<option value="recaptcha" <?php selected( $s['captcha_provider'], 'recaptcha' ); ?>><?php esc_html_e( 'Google reCAPTCHA v2 (checkbox)', 'wp-waiver' ); ?></option>
							<option value="turnstile" <?php selected( $s['captcha_provider'], 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'wp-waiver' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'The honeypot, time-trap, and rate-limit layers are always on. A captcha adds a visible challenge on top.', 'wp-waiver' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpw-captcha-site-key"><?php esc_html_e( 'Captcha site key', 'wp-waiver' ); ?></label></th>
					<td><input name="wpw_settings[captcha_site_key]" id="wpw-captcha-site-key" type="text" class="regular-text" value="<?php echo esc_attr( $s['captcha_site_key'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpw-captcha-secret"><?php esc_html_e( 'Captcha secret key', 'wp-waiver' ); ?></label></th>
					<td><input name="wpw_settings[captcha_secret]" id="wpw-captcha-secret" type="password" class="regular-text" value="<?php echo esc_attr( $s['captcha_secret'] ); ?>" autocomplete="new-password" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
