<?php
/**
 * Uninstall: remove plugin settings only.
 *
 * Signed waiver records and waiver documents are legal records — they are
 * intentionally NOT deleted. Remove them manually from wp-admin (or via
 * WP-CLI) if you truly want them gone.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpw_settings' );
