<?php
/**
 * [wp_waiver_form] markup: confirmation state, or the waiver form
 * (participant → agreement + acceptance → handwritten pad + printed name).
 * Class names match the triple-a theme's stylesheet; the plugin renders
 * structure only and leaves styling to the active theme.
 */

defined( 'ABSPATH' ) || exit;

$aaa_armory_url = post_type_exists( 'firearm' ) ? get_post_type_archive_link( 'firearm' ) : home_url( '/' );
?>
<?php if ( isset( $_GET['signed'] ) ) : ?>

	<div class="card confirm">
		<div class="checkmark">&#10003;</div>
		<h2 class="disp h2" style="font-size:clamp(30px,4vw,44px)">Waiver received</h2>
		<p class="lead" style="max-width:46ch">You're clear to shoot. Show your photo ID at the counter when you check in &mdash; your signed waiver is on file.</p>
		<div class="confirmbtns">
			<a class="btn btn-grn" href="<?php echo esc_url( $aaa_armory_url ); ?>">Browse the armory</a>
			<a class="btn btn-line" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
		</div>
	</div>

<?php else : ?>

	<?php if ( isset( $_GET['err'] ) ) : ?>
		<p class="errnote">Please complete every required field, accept the Agreement, and sign.</p>
	<?php endif; ?>

	<form class="card formcard" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="aaa_waiver" />
		<?php wp_nonce_field( 'aaa_waiver', 'aaa_waiver_nonce' ); ?>
		<p class="hp"><label>Leave this field empty <input type="text" name="aaa_website" tabindex="-1" autocomplete="off" /></label></p>

		<div class="formsec">
			<h2 class="sechead"><span class="num">1</span>Participant</h2>
			<div class="fgrid">
				<div class="fld full">
					<label class="lab" for="w-name">Full name <i>*</i></label>
					<input class="in" id="w-name" name="w_name" type="text" placeholder="Your name" autocomplete="name" required />
				</div>
				<div class="fld">
					<label class="lab" for="w-email">Email <i>*</i></label>
					<input class="in" id="w-email" name="w_email" type="email" placeholder="you@example.com" autocomplete="email" required />
				</div>
				<div class="fld">
					<label class="lab" for="w-phone">Phone <i>*</i></label>
					<input class="in" id="w-phone" name="w_phone" type="tel" placeholder="(555) 000-0000" autocomplete="tel" required />
				</div>
			</div>
		</div>

		<hr class="divider" />

		<div class="formsec">
			<h2 class="sechead"><span class="num">2</span>Waiver &amp; Release Agreement</h2>
			<div class="terms">
				<?php include WP_WAIVER_DIR . 'templates/agreement.php'; ?>
			</div>
			<label class="ckrow">
				<input type="checkbox" name="w_ack" value="1" required />
				<span>I represent and warrant that I have read and understood all of the provisions contained in this Agreement, and I agree to be bound by all of its terms and conditions.</span>
			</label>
		</div>

		<hr class="divider" />

		<div class="formsec">
			<h2 class="sechead"><span class="num">3</span>Signature</h2>
			<div class="fld">
				<label class="lab">Sign here <i>*</i></label>
				<div class="sigpadwrap">
					<canvas id="aaa-sigpad" class="sigpad"></canvas>
					<button type="button" id="aaa-sig-clear" class="sigclear">Clear</button>
				</div>
				<p class="errnote" id="aaa-sig-hint" style="display:none;margin:4px 0 0;font-size:14px">Please sign in the box above before submitting.</p>
				<input type="hidden" name="w_sig_img" id="aaa-sig-data" value="" />
			</div>
			<div class="sigrow">
				<div class="fld">
					<label class="lab" for="w-sig">Full legal name (print) <i>*</i></label>
					<input class="in" id="w-sig" name="w_sig" type="text" placeholder="Type your full legal name" required minlength="3" />
				</div>
				<div class="fld">
					<label class="lab">Date</label>
					<div class="dateval"><?php echo esc_html( wp_date( 'F j, Y' ) ); ?></div>
				</div>
			</div>
			<p class="signote">Your handwritten signature above, together with your printed legal name, constitutes your electronic signature on this Agreement.</p>
			<button class="btn btn-amb" type="submit" style="align-self:flex-start;margin-top:6px">Submit waiver</button>
		</div>
	</form>

<?php endif; ?>
