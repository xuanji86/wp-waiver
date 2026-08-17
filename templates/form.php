<?php
/**
 * [wp_waiver_form] markup: confirmation state, or the waiver form
 * (participant → agreement + acceptance → handwritten pad + printed name →
 * optional captcha). All classes are wpw- prefixed; assets/wp-waiver.css
 * ships neutral defaults driven by --wpw-* custom properties that any theme
 * can override.
 *
 * Available: $doc (WP_Post, the resolved waiver document).
 */

defined( 'ABSPATH' ) || exit;

$wpw_state  = isset( $_GET['wpw'] ) ? sanitize_key( $_GET['wpw'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wpw_return = esc_url( remove_query_arg( 'wpw' ) );
?>
<div class="wpw">
<?php if ( 'signed' === $wpw_state ) : ?>

	<?php
	$wpw_confirm  = '<div class="wpw-confirm">';
	$wpw_confirm .= '<div class="wpw-checkmark">&#10003;</div>';
	$wpw_confirm .= '<h2 class="wpw-confirm-title">' . esc_html__( 'Waiver received', 'wp-waiver' ) . '</h2>';
	$wpw_confirm .= '<p class="wpw-confirm-text">' . esc_html__( 'Thank you — your signed waiver is on file.', 'wp-waiver' ) . '</p>';
	$wpw_confirm .= '</div>';
	echo wp_kses_post( apply_filters( 'wpw_confirmation_html', $wpw_confirm, $doc ) );
	?>

<?php else : ?>

	<?php if ( 'err' === $wpw_state ) : ?>
		<p class="wpw-error"><?php esc_html_e( 'Please complete every required field, accept the Agreement, and sign.', 'wp-waiver' ); ?></p>
	<?php elseif ( 'captcha' === $wpw_state ) : ?>
		<p class="wpw-error"><?php esc_html_e( 'The captcha check did not pass. Please try again.', 'wp-waiver' ); ?></p>
	<?php endif; ?>

	<form class="wpw-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wpw_submit" />
		<input type="hidden" name="wpw_doc" value="<?php echo esc_attr( $doc->ID ); ?>" />
		<input type="hidden" name="wpw_tt" value="<?php echo esc_attr( wpw_timetrap_token( $doc->ID ) ); ?>" />
		<input type="hidden" name="wpw_return" value="<?php echo esc_attr( $wpw_return ); ?>" />
		<?php wp_nonce_field( 'wpw_submit', 'wpw_nonce' ); ?>
		<p class="wpw-hp"><label><?php esc_html_e( 'Leave this field empty', 'wp-waiver' ); ?> <input type="text" name="wpw_website" tabindex="-1" autocomplete="off" /></label></p>

		<div class="wpw-section">
			<h2 class="wpw-sechead"><span class="wpw-num">1</span><?php esc_html_e( 'Participant', 'wp-waiver' ); ?></h2>
			<div class="wpw-grid">
				<div class="wpw-field wpw-full">
					<label class="wpw-label" for="wpw-name"><?php esc_html_e( 'Full name', 'wp-waiver' ); ?> <i class="wpw-req">*</i></label>
					<input class="wpw-input" id="wpw-name" name="wpw_name" type="text" placeholder="<?php esc_attr_e( 'Your name', 'wp-waiver' ); ?>" autocomplete="name" required />
				</div>
				<div class="wpw-field">
					<label class="wpw-label" for="wpw-email"><?php esc_html_e( 'Email', 'wp-waiver' ); ?> <i class="wpw-req">*</i></label>
					<input class="wpw-input" id="wpw-email" name="wpw_email" type="email" placeholder="you@example.com" autocomplete="email" required />
				</div>
				<div class="wpw-field">
					<label class="wpw-label" for="wpw-phone"><?php esc_html_e( 'Phone', 'wp-waiver' ); ?> <i class="wpw-req">*</i></label>
					<input class="wpw-input" id="wpw-phone" name="wpw_phone" type="tel" placeholder="(555) 000-0000" autocomplete="tel" required />
				</div>
			</div>
		</div>

		<hr class="wpw-divider" />

		<div class="wpw-section">
			<h2 class="wpw-sechead"><span class="wpw-num">2</span><?php echo esc_html( $doc->post_title ); ?></h2>
			<div class="wpw-terms"><?php echo wp_kses_post( wpw_render_agreement( $doc ) ); ?></div>
			<label class="wpw-ack">
				<input type="checkbox" name="wpw_ack" value="1" required />
				<span><?php esc_html_e( 'I represent and warrant that I have read and understood all of the provisions contained in this Agreement, and I agree to be bound by all of its terms and conditions.', 'wp-waiver' ); ?></span>
			</label>
		</div>

		<hr class="wpw-divider" />

		<div class="wpw-section">
			<h2 class="wpw-sechead"><span class="wpw-num">3</span><?php esc_html_e( 'Signature', 'wp-waiver' ); ?></h2>
			<div class="wpw-field">
				<label class="wpw-label"><?php esc_html_e( 'Sign here', 'wp-waiver' ); ?> <i class="wpw-req">*</i></label>
				<div class="wpw-sigpad-wrap">
					<canvas id="wpw-sigpad" class="wpw-sigpad"></canvas>
					<button type="button" id="wpw-sig-clear" class="wpw-sig-clear"><?php esc_html_e( 'Clear', 'wp-waiver' ); ?></button>
				</div>
				<p class="wpw-error" id="wpw-sig-hint" style="display:none"><?php esc_html_e( 'Please sign in the box above before submitting.', 'wp-waiver' ); ?></p>
				<input type="hidden" name="wpw_sig_img" id="wpw-sig-data" value="" />
			</div>
			<div class="wpw-sigrow">
				<div class="wpw-field">
					<label class="wpw-label" for="wpw-sig-typed"><?php esc_html_e( 'Full legal name (print)', 'wp-waiver' ); ?> <i class="wpw-req">*</i></label>
					<input class="wpw-input" id="wpw-sig-typed" name="wpw_sig_typed" type="text" placeholder="<?php esc_attr_e( 'Type your full legal name', 'wp-waiver' ); ?>" required minlength="3" />
				</div>
				<div class="wpw-field">
					<label class="wpw-label"><?php esc_html_e( 'Date', 'wp-waiver' ); ?></label>
					<div class="wpw-dateval"><?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?></div>
				</div>
			</div>
			<p class="wpw-note"><?php esc_html_e( 'Your handwritten signature above, together with your printed legal name, constitutes your electronic signature on this Agreement.', 'wp-waiver' ); ?></p>

			<?php if ( wpw_captcha_provider() ) : ?>
				<div class="wpw-captcha"><?php echo wpw_captcha_widget_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

			<button class="wpw-btn" type="submit"><?php esc_html_e( 'Submit waiver', 'wp-waiver' ); ?></button>
		</div>
	</form>

<?php endif; ?>
</div>
