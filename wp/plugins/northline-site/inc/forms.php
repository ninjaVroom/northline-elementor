<?php
/**
 * First-party intake (replaces Gravity Forms in the free build).
 * Every submission is stored as a private nl_inquiry record before any email is sent,
 * carries a unique token so a double submit resolves to the same record, and redirects
 * with only an inquiry number or an error code in the URL (no personal data).
 * no CRM adapter or retry queue yet; add an outbound worker when HubSpot credentials exist.
 */

defined( 'ABSPATH' ) || exit;

const NL_FORM_ERRORS = [
	'nonce'   => 'Your session expired. Please submit the form again.',
	'email'   => 'Please enter a valid business email address.',
	'company' => 'Please enter your company name.',
	'role'    => 'Please enter your role.',
	'plants'  => 'Please enter a plant count between 1 and 99.',
	'consent' => 'Please confirm we may contact you about this inquiry.',
];

function nl_inquiry_form( array $args = [] ): string {
	$error   = sanitize_key( $_GET['nl_error'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$done    = sanitize_text_field( wp_unslash( $_GET['inquiry'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$success = nl_opt( 'form_success', 'Thank you. A principal will reply within one business day.' );
	$intro   = $args['intro'] ?? '';
	ob_start();
	?>
	<div class="nl-form-wrap" id="nl-form">
		<?php if ( $done && preg_match( '/^NL-\d{4}-\d+$/', $done ) ) : ?>
			<div class="nl-form-done" role="status" tabindex="-1">
				<h2 class="nl-h2">Inquiry received</h2>
				<p class="nl-read"><?php echo esc_html( $success ); ?></p>
				<p class="nl-form-done__id">Reference <strong><?php echo esc_html( $done ); ?></strong></p>
				<?php echo nl_link( nl_opt( 'header_cta', null ), 'nl-btn' ); // phpcs:ignore ?>
			</div>
		<?php else : ?>
			<form class="nl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-steps>
				<?php if ( $error && isset( NL_FORM_ERRORS[ $error ] ) ) : ?>
					<div class="nl-form__summary" role="alert" tabindex="-1"><p>Please correct the error below.</p><p><a href="#nl-f-<?php echo esc_attr( $error ); ?>"><?php echo esc_html( NL_FORM_ERRORS[ $error ] ); ?></a></p></div>
				<?php endif; ?>
				<input type="hidden" name="action" value="nl_inquiry">
				<input type="hidden" name="token" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
				<input type="hidden" name="return" value="<?php echo esc_url( get_permalink() ); ?>">
				<input type="hidden" name="utm" value="" data-utm>
				<?php wp_nonce_field( 'nl_inquiry', 'nl_nonce' ); ?>
				<p class="nl-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
				<?php if ( $intro ) : ?><p class="nl-read"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
				<p class="nl-form__progress" aria-live="polite" data-progress>Step 1 of 3: interest and work email</p>

				<fieldset class="nl-form__step" data-step="1" data-title="interest and work email">
					<legend class="nl-h3">Interest and work email</legend>
					<?php nl_select( 'interest', 'Primary interest', array_merge( array_map( fn( $s ) => $s->post_title, nl_services() ), [ 'Not sure yet' ] ), true ); ?>
					<?php nl_input( 'email', 'Work email', 'email', true, [ 'autocomplete' => 'email', 'inputmode' => 'email' ], 'email' === $error ); ?>
				</fieldset>

				<fieldset class="nl-form__step" data-step="2" data-title="plant context">
					<legend class="nl-h3">Plant context</legend>
					<div class="nl-form__row">
						<?php nl_input( 'company', 'Company', 'text', true, [ 'autocomplete' => 'organization', 'maxlength' => 120 ], 'company' === $error ); ?>
						<?php nl_input( 'role', 'Role', 'text', true, [ 'autocomplete' => 'organization-title', 'maxlength' => 80 ], 'role' === $error ); ?>
					</div>
					<?php nl_input( 'plants', 'Plant count', 'number', true, [ 'min' => 1, 'max' => 99, 'inputmode' => 'numeric' ], 'plants' === $error ); ?>
					<div class="nl-field">
						<label for="nl-f-bottleneck">Where is the bottleneck? <span class="nl-field__opt">Optional</span></label>
						<textarea id="nl-f-bottleneck" name="bottleneck" rows="4" maxlength="1000"></textarea>
					</div>
				</fieldset>

				<fieldset class="nl-form__step" data-step="3" data-title="review and consent">
					<legend class="nl-h3">Review and consent</legend>
					<dl class="nl-form__review" data-review hidden></dl>
					<div class="nl-field nl-field--check<?php echo 'consent' === $error ? ' is-invalid' : ''; ?>">
						<input type="checkbox" id="nl-f-consent" name="consent" value="1" required<?php echo 'consent' === $error ? ' aria-invalid="true" aria-describedby="nl-e-consent"' : ''; ?>>
						<label for="nl-f-consent">Northline may contact me about this inquiry. See the <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</label>
						<?php if ( 'consent' === $error ) : ?><p class="nl-field__err" id="nl-e-consent"><?php echo esc_html( NL_FORM_ERRORS['consent'] ); ?></p><?php endif; ?>
					</div>
				</fieldset>

				<div class="nl-form__nav">
					<button type="button" class="nl-btn nl-btn--ghost" data-prev hidden>Back</button>
					<button type="button" class="nl-btn" data-next hidden>Continue</button>
					<button type="submit" class="nl-btn" data-submit>Send inquiry</button>
				</div>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function nl_input( string $name, string $label, string $type, bool $req, array $attrs = [], bool $invalid = false ): void {
	$id   = 'nl-f-' . $name;
	$attr = '';
	foreach ( $attrs as $k => $v ) {
		$attr .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( (string) $v ) );
	}
	printf(
		'<div class="nl-field%1$s"><label for="%2$s">%3$s%4$s</label><input id="%2$s" name="%5$s" type="%6$s"%7$s%8$s>%9$s</div>',
		$invalid ? ' is-invalid' : '',
		esc_attr( $id ),
		esc_html( $label ),
		$req ? ' <span class="nl-field__req" aria-hidden="true">*</span>' : '',
		esc_attr( $name ),
		esc_attr( $type ),
		$req ? ' required' : '',
		$attr . ( $invalid ? ' aria-invalid="true" aria-describedby="nl-e-' . esc_attr( $name ) . '"' : '' ),
		$invalid ? '<p class="nl-field__err" id="nl-e-' . esc_attr( $name ) . '">' . esc_html( NL_FORM_ERRORS[ $name ] ?? 'Please check this field.' ) . '</p>' : ''
	);
}

function nl_select( string $name, string $label, array $options, bool $req ): void {
	printf( '<div class="nl-field"><label for="nl-f-%1$s">%2$s%3$s</label><span class="nl-select"><select id="nl-f-%1$s" name="%1$s"%4$s><button><selectedcontent></selectedcontent></button>', esc_attr( $name ), esc_html( $label ), $req ? ' <span class="nl-field__req" aria-hidden="true">*</span>' : '', $req ? ' required' : '' );
	foreach ( $options as $o ) {
		printf( '<option>%s</option>', esc_html( $o ) );
	}
	echo '</select></span></div>';
}

/** Validates an inquiry payload. Returns an error code or '' when valid. */
function nl_validate_inquiry( array $in ): string {
	if ( ! is_email( $in['email'] ?? '' ) ) {
		return 'email';
	}
	foreach ( [ 'company', 'role' ] as $k ) {
		if ( '' === trim( $in[ $k ] ?? '' ) ) {
			return $k;
		}
	}
	$plants = filter_var( $in['plants'] ?? '', FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1, 'max_range' => 99 ] ] );
	if ( false === $plants ) {
		return 'plants';
	}
	return empty( $in['consent'] ) ? 'consent' : '';
}

function nl_handle_inquiry(): void {
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return'] ?? '' ) ), home_url( '/contact/' ) );
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nl_nonce'] ?? '' ) ), 'nl_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'nl_error', 'nonce', $back ) . '#nl-form' );
		exit;
	}
	if ( ! empty( $_POST['website'] ) ) { // Honeypot: pretend success, store nothing.
		wp_safe_redirect( add_query_arg( 'inquiry', 'NL-' . gmdate( 'Y' ) . '-0', $back ) . '#nl-form' );
		exit;
	}
	$in = [
		'interest'   => sanitize_text_field( wp_unslash( $_POST['interest'] ?? '' ) ),
		'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'company'    => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
		'role'       => sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) ),
		'plants'     => sanitize_text_field( wp_unslash( $_POST['plants'] ?? '' ) ),
		'bottleneck' => sanitize_textarea_field( wp_unslash( $_POST['bottleneck'] ?? '' ) ),
		'consent'    => ! empty( $_POST['consent'] ),
		'utm'        => sanitize_text_field( wp_unslash( $_POST['utm'] ?? '' ) ),
	];
	if ( $err = nl_validate_inquiry( $in ) ) {
		wp_safe_redirect( add_query_arg( 'nl_error', $err, $back ) . '#nl-form' );
		exit;
	}
	$token = sanitize_key( wp_unslash( $_POST['token'] ?? '' ) );
	$id    = nl_store_submission( 'inquiry', $token, $in['company'], $in );
	nl_notify( $id, $in );
	wp_safe_redirect( add_query_arg( 'inquiry', nl_ref( $id ), $back ) . '#nl-form' );
	exit;
}
add_action( 'admin_post_nl_inquiry', 'nl_handle_inquiry' );
add_action( 'admin_post_nopriv_nl_inquiry', 'nl_handle_inquiry' );

/** Optional "email me the checklist" (G-1). The guide itself is never gated. */
function nl_handle_checklist(): void {
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return'] ?? '' ) ), home_url( '/' ) );
	$ok   = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nl_nonce'] ?? '' ) ), 'nl_checklist' );
	$mail = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! $ok || ! is_email( $mail ) ) {
		wp_safe_redirect( add_query_arg( 'checklist', 'error', $back ) . '#nl-checklist' );
		exit;
	}
	if ( empty( $_POST['website'] ) ) {
		$id = nl_store_submission( 'checklist', sanitize_key( wp_unslash( $_POST['token'] ?? '' ) ), $mail, [ 'email' => $mail ] );
		if ( ! get_post_meta( $id, '_nl_mailed', true ) ) {
			$file = nl_field( 'guide_file', url_to_postid( $back ) );
			wp_mail( $mail, 'Your Operations Review checklist', "Here is the printable checklist:\n\n" . ( $file ? wp_get_attachment_url( $file ) : home_url( '/guides/operations-review/' ) ) );
			update_post_meta( $id, '_nl_mailed', 1 );
		}
	}
	wp_safe_redirect( add_query_arg( 'checklist', 'sent', $back ) . '#nl-checklist' );
	exit;
}
add_action( 'admin_post_nl_checklist', 'nl_handle_checklist' );
add_action( 'admin_post_nopriv_nl_checklist', 'nl_handle_checklist' );

/** Stores once per token; a repeated token returns the existing record. */
function nl_store_submission( string $kind, string $token, string $title, array $data ): int {
	if ( $token && ( $existing = get_posts( [ 'post_type' => 'nl_inquiry', 'post_status' => 'any', 'fields' => 'ids', 'meta_key' => '_nl_token', 'meta_value' => $token, 'posts_per_page' => 1 ] ) ) ) {
		return (int) $existing[0];
	}
	$id = wp_insert_post( [ 'post_type' => 'nl_inquiry', 'post_status' => 'private', 'post_title' => ucfirst( $kind ) . ': ' . $title ], true );
	if ( is_wp_error( $id ) ) {
		wp_die( 'We could not save your request. Please email us directly.', 'Submission error', [ 'response' => 500 ] );
	}
	update_post_meta( $id, '_nl_token', $token );
	update_post_meta( $id, '_nl_kind', $kind );
	foreach ( $data as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	wp_update_post( [ 'ID' => $id, 'post_title' => nl_ref( $id ) . ' ' . ucfirst( $kind ) . ': ' . $title ] );
	return (int) $id;
}

function nl_ref( int $id ): string {
	return 'NL-' . get_the_date( 'Y', $id ) . '-' . $id;
}

function nl_notify( int $id, array $in ): void {
	if ( get_post_meta( $id, '_nl_mailed', true ) ) {
		return;
	}
	$body = sprintf( "%s\n\nInterest: %s\nEmail: %s\nCompany: %s\nRole: %s\nPlants: %s\n\n%s", nl_ref( $id ), $in['interest'], $in['email'], $in['company'], $in['role'], $in['plants'], $in['bottleneck'] );
	$sent = wp_mail( nl_opt( 'email', get_option( 'admin_email' ) ), 'New inquiry ' . nl_ref( $id ), $body, [ 'Reply-To: ' . $in['email'] ] );
	update_post_meta( $id, '_nl_mailed', $sent ? 1 : 0 ); // 0 = stored but not yet delivered; visible in the admin list.
}

/** Consent preferences control (cookies page). Stored in a first-party cookie by northline.js. */
add_shortcode( 'nl_consent_preferences', function () {
	$rows = [
		[ 'essential', 'Essential session', 'Required. Keeps forms and security working.', true ],
		[ 'analytics', 'Analytics', 'Off until you choose. Measures page performance without personal data.', false ],
		[ 'scheduling', 'Scheduling embed', 'Loaded only when you open the booking calendar.', false ],
	];
	ob_start();
	?>
	<form class="nl-consent" id="preferences" data-consent>
		<fieldset><legend class="nl-h3">Cookie preferences</legend>
			<?php foreach ( $rows as [ $k, $label, $help, $locked ] ) : ?>
				<div class="nl-field nl-field--check">
					<input type="checkbox" id="nl-c-<?php echo esc_attr( $k ); ?>" name="<?php echo esc_attr( $k ); ?>" <?php echo $locked ? 'checked disabled' : ''; ?>>
					<label for="nl-c-<?php echo esc_attr( $k ); ?>"><strong><?php echo esc_html( $label ); ?></strong> <?php echo esc_html( $help ); ?></label>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<div class="nl-actions"><button class="nl-btn" type="submit">Save preferences</button><button class="nl-btn nl-btn--ghost" type="button" data-consent-reject>Reject optional</button></div>
		<p class="nl-consent__status" role="status" data-consent-status></p>
	</form>
	<?php
	return (string) ob_get_clean();
} );

/* ---------------------------------------------------------------------------
 * First-party scheduler. Slots are 30-minute calls on the next 10 business days in the
 * site timezone. A booking is stored before any email is sent; a taken slot is refused.
 * availability is one shared calendar with fixed hours; add per-partner calendars
 * or a two-way sync (Google/Outlook) when partners book independently.
 */
const NL_SLOT_TIMES = [ '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00' ];

/** @return array<string, string[]> 'Y-m-d' => ['Y-m-d H:i', ...] for the next $days business days, past times removed. */
function nl_slot_days( int $days = 10 ): array {
	$tz  = wp_timezone();
	$now = new DateTimeImmutable( 'now', $tz );
	$d   = $now->setTime( 0, 0 );
	$out = [];
	while ( count( $out ) < $days ) {
		if ( (int) $d->format( 'N' ) <= 5 ) {
			$slots = [];
			foreach ( NL_SLOT_TIMES as $t ) {
				$at = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $d->format( 'Y-m-d' ) . ' ' . $t, $tz );
				if ( $at > $now->modify( '+2 hours' ) ) {
					$slots[] = $at->format( 'Y-m-d H:i' );
				}
			}
			if ( $slots ) {
				$out[ $d->format( 'Y-m-d' ) ] = $slots;
			}
		}
		$d = $d->modify( '+1 day' );
	}
	return $out;
}

/** Slots already booked, as a set of 'Y-m-d H:i'. */
function nl_taken_slots(): array {
	$ids = get_posts( [ 'post_type' => 'nl_inquiry', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => -1, 'meta_key' => '_nl_kind', 'meta_value' => 'booking', 'date_query' => [ [ 'after' => '-30 days' ] ] ] );
	return array_fill_keys( array_filter( array_map( fn( $id ) => get_post_meta( $id, 'slot', true ), $ids ) ), true );
}

function nl_booking_form(): string {
	$done  = sanitize_text_field( wp_unslash( $_GET['booked'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$error = sanitize_key( $_GET['nl_error'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$tz    = wp_timezone();
	ob_start();
	if ( $done && preg_match( '/^NL-\d{4}-\d+$/', $done ) && ( $id = (int) substr( strrchr( $done, '-' ), 1 ) ) && 'booking' === get_post_meta( $id, '_nl_kind', true ) ) :
		$at = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', (string) get_post_meta( $id, 'slot', true ), $tz );
		?>
		<div class="nl-form-done" id="nl-book" role="status" tabindex="-1">
			<h2 class="nl-h2">You’re booked</h2>
			<p class="nl-book-done__when"><?php echo esc_html( $at ? $at->format( 'l, F j' ) : '' ); ?><br><span><?php echo esc_html( $at ? $at->format( 'g:i a' ) . ' to ' . $at->modify( '+30 minutes' )->format( 'g:i a' ) . ' ' . $at->format( 'T' ) : '' ); ?></span></p>
			<p class="nl-read">A calendar invitation is on its way to your inbox. Reference <strong><?php echo esc_html( $done ); ?></strong>.</p>
			<a class="nl-textlink" href="<?php echo esc_url( get_permalink() ); ?>#nl-book"><span>Book another time</span><?php echo nl_arrow(); // phpcs:ignore ?></a>
		</div>
		<?php
		return (string) ob_get_clean();
	endif;

	$days  = nl_slot_days();
	$taken = nl_taken_slots();
	$first = array_key_first( $days );
	$errs  = [ 'slot' => 'That time was just taken or is no longer available. Please pick another.', 'email' => NL_FORM_ERRORS['email'], 'name' => 'Please enter your name.', 'company' => NL_FORM_ERRORS['company'], 'consent' => NL_FORM_ERRORS['consent'], 'nonce' => NL_FORM_ERRORS['nonce'] ];
	?>
	<form class="nl-form nl-sched" id="nl-book" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-sched>
		<?php if ( isset( $errs[ $error ] ) ) : ?>
			<div class="nl-form__summary" role="alert" tabindex="-1"><p><?php echo esc_html( $errs[ $error ] ); ?></p></div>
		<?php endif; ?>
		<input type="hidden" name="action" value="nl_booking">
		<input type="hidden" name="token" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
		<input type="hidden" name="return" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'nl_booking', 'nl_nonce' ); ?>
		<p class="nl-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>

		<fieldset class="nl-sched__days">
			<legend class="nl-h3">Pick a day</legend>
			<div class="nl-sched__daylist">
				<?php foreach ( $days as $day => $slots ) :
					$dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $day, $tz );
					$free = count( array_filter( $slots, fn( $s ) => empty( $taken[ $s ] ) ) );
					?>
					<label class="nl-day<?php echo $free ? '' : ' is-full'; ?>">
						<input type="radio" name="day" value="<?php echo esc_attr( $day ); ?>" <?php checked( $day, $first ); ?> <?php disabled( ! $free ); ?>>
						<span class="nl-day__dow"><?php echo esc_html( $dt->format( 'D' ) ); ?></span>
						<span class="nl-day__num"><?php echo esc_html( $dt->format( 'j' ) ); ?></span>
						<span class="nl-day__mon"><?php echo esc_html( $dt->format( 'M' ) ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset class="nl-sched__times">
			<legend class="nl-h3">Pick a time <span class="nl-sched__tz">(<?php echo esc_html( wp_timezone_string() ); ?>)</span></legend>
			<?php foreach ( $days as $day => $slots ) :
				$dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $day, $tz );
				?>
				<div class="nl-slots" data-day="<?php echo esc_attr( $day ); ?>">
					<p class="nl-slots__day"><?php echo esc_html( $dt->format( 'l, F j' ) ); ?></p>
					<div class="nl-slots__grid">
						<?php foreach ( $slots as $s ) :
							$at = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $s, $tz );
							?>
							<label class="nl-slot"><input type="radio" name="slot" value="<?php echo esc_attr( $s ); ?>" required <?php disabled( ! empty( $taken[ $s ] ) ); ?>><span><?php echo esc_html( $at->format( 'g:i a' ) ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</fieldset>

		<fieldset class="nl-form__step">
			<legend class="nl-h3">Your details</legend>
			<div class="nl-form__row">
				<?php nl_input( 'name', 'Name', 'text', true, [ 'autocomplete' => 'name', 'maxlength' => 100 ], 'name' === $error ); ?>
				<?php nl_input( 'email', 'Work email', 'email', true, [ 'autocomplete' => 'email' ], 'email' === $error ); ?>
			</div>
			<?php nl_input( 'company', 'Company', 'text', true, [ 'autocomplete' => 'organization', 'maxlength' => 120 ], 'company' === $error ); ?>
			<div class="nl-field">
				<label for="nl-f-topic">What should we look at? <span class="nl-field__opt">Optional</span></label>
				<textarea id="nl-f-topic" name="topic" rows="3" maxlength="600"></textarea>
			</div>
			<div class="nl-field nl-field--check<?php echo 'consent' === $error ? ' is-invalid' : ''; ?>">
				<input type="checkbox" id="nl-f-bconsent" name="consent" value="1" required>
				<label for="nl-f-bconsent">Northline may email me about this call. See the <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</label>
			</div>
		</fieldset>

		<p class="nl-sched__summary" aria-live="polite" data-sched-summary></p>
		<button class="nl-btn" type="submit">Confirm booking</button>
		<?php if ( $url = nl_opt( 'booking_url' ) ) : ?>
			<p class="nl-booking__alt">Prefer your own calendar tool? <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">Open our scheduling page<span class="nl-sr"> (new tab)</span></a>.</p>
		<?php endif; ?>
	</form>
	<?php
	return (string) ob_get_clean();
}

/** Validates a booking payload against the offered, untaken slots. Returns an error code or ''. */
function nl_validate_booking( array $in, array $days, array $taken ): string {
	$offered = array_merge( ...array_values( $days ?: [ [] ] ) );
	if ( ! in_array( $in['slot'], $offered, true ) || ! empty( $taken[ $in['slot'] ] ) ) {
		return 'slot';
	}
	if ( '' === trim( $in['name'] ) ) {
		return 'name';
	}
	if ( ! is_email( $in['email'] ) ) {
		return 'email';
	}
	if ( '' === trim( $in['company'] ) ) {
		return 'company';
	}
	return $in['consent'] ? '' : 'consent';
}

function nl_handle_booking(): void {
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return'] ?? '' ) ), home_url( '/book/' ) );
	$fail = fn( $code ) => wp_safe_redirect( add_query_arg( 'nl_error', $code, $back ) . '#nl-book' ) && exit;
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nl_nonce'] ?? '' ) ), 'nl_booking' ) ) {
		$fail( 'nonce' );
	}
	$token = sanitize_key( wp_unslash( $_POST['token'] ?? '' ) );
	if ( $token && ( $prev = get_posts( [ 'post_type' => 'nl_inquiry', 'post_status' => 'any', 'fields' => 'ids', 'meta_key' => '_nl_token', 'meta_value' => $token, 'posts_per_page' => 1 ] ) ) ) {
		wp_safe_redirect( add_query_arg( 'booked', nl_ref( (int) $prev[0] ), $back ) . '#nl-book' ); // Double submit: same booking.
		exit;
	}
	if ( ! empty( $_POST['website'] ) ) {
		$fail( 'slot' );
	}
	$in = [
		'slot'    => sanitize_text_field( wp_unslash( $_POST['slot'] ?? '' ) ),
		'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'email'   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'company' => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
		'topic'   => sanitize_textarea_field( wp_unslash( $_POST['topic'] ?? '' ) ),
		'consent' => ! empty( $_POST['consent'] ),
	];
	if ( $err = nl_validate_booking( $in, nl_slot_days(), nl_taken_slots() ) ) {
		$fail( $err );
	}
	$id = nl_store_submission( 'booking', $token, $in['company'] . ', ' . $in['slot'], $in );
	nl_booking_mail( $id, $in );
	wp_safe_redirect( add_query_arg( 'booked', nl_ref( $id ), $back ) . '#nl-book' );
	exit;
}
add_action( 'admin_post_nl_booking', 'nl_handle_booking' );
add_action( 'admin_post_nopriv_nl_booking', 'nl_handle_booking' );

/** Confirmation to the visitor and notice to the partners, both with an .ics invitation. */
function nl_booking_mail( int $id, array $in ): void {
	if ( get_post_meta( $id, '_nl_mailed', true ) ) {
		return;
	}
	$tz    = wp_timezone();
	$start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $in['slot'], $tz );
	$utc   = fn( DateTimeImmutable $d ) => $d->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' );
	$org   = nl_opt( 'email', get_option( 'admin_email' ) );
	$esc   = fn( $s ) => addcslashes( (string) $s, ',;\\' );
	$ics   = implode( "\r\n", [
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Northline//Scheduler//EN', 'METHOD:REQUEST', 'BEGIN:VEVENT',
		'UID:' . nl_ref( $id ) . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART:' . $utc( $start ),
		'DTEND:' . $utc( $start->modify( '+30 minutes' ) ),
		'SUMMARY:' . $esc( 'Northline floor review: ' . $in['company'] ),
		'DESCRIPTION:' . $esc( '30-minute scoping call. Reference ' . nl_ref( $id ) . ( $in['topic'] ? '. Topic: ' . $in['topic'] : '' ) ),
		'ORGANIZER;CN=Northline Operations Advisory:mailto:' . $org,
		'ATTENDEE;CN=' . $esc( $in['name'] ) . ';RSVP=TRUE:mailto:' . $in['email'],
		'END:VEVENT', 'END:VCALENDAR', '',
	] );
	$file = trailingslashit( get_temp_dir() ) . 'northline-' . nl_ref( $id ) . '.ics';
	file_put_contents( $file, $ics ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$when = $start->format( 'l, F j, g:i a T' );
	$ok1  = wp_mail( $in['email'], 'Booked: Northline floor review, ' . $when, "Hi {$in['name']},\n\nYour 30-minute call is booked for {$when}.\nReference: " . nl_ref( $id ) . "\n\nThe calendar invitation is attached.\n\nNorthline Operations Advisory", [ 'Reply-To: ' . $org ], [ $file ] );
	$ok2  = wp_mail( $org, 'New booking ' . nl_ref( $id ) . ': ' . $in['company'] . ', ' . $when, "{$in['name']} <{$in['email']}>\n{$in['company']}\n\n{$in['topic']}", [ 'Reply-To: ' . $in['email'] ], [ $file ] );
	wp_delete_file( $file );
	update_post_meta( $id, '_nl_mailed', ( $ok1 && $ok2 ) ? 1 : 0 );
}

/** Booking aside: partner and agenda, plus an alternative route. */
function nl_booking_html( array $a ): string {
	ob_start();
	?>
	<div class="nl-booking">
		<?php if ( ! empty( $a['person'] ) ) : $p = (int) $a['person']; ?>
			<div class="nl-booking__who">
				<?php echo nl_img( nl_field( 'portrait', $p ), 'thumbnail', [ 'alt' => '' ] ); // phpcs:ignore ?>
				<div><p class="nl-h3"><?php echo esc_html( get_the_title( $p ) ); ?></p><p class="nl-read"><?php echo esc_html( nl_field( 'job_title', $p ) ); ?></p></div>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $a['agenda'] ) ) : ?>
			<ol class="nl-booking__agenda" role="list">
				<?php foreach ( $a['agenda'] as $row ) : ?><li><span class="nl-booking__min"><?php echo esc_html( $row['minutes'] ); ?></span><span><?php echo esc_html( $row['topic'] ); ?></span></li><?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<p class="nl-booking__alt">Prefer email? <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Use the contact form</a> or call <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', nl_opt( 'phone' ) ) ); ?>"><?php echo esc_html( nl_opt( 'phone' ) ); ?></a>.</p>
	</div>
	<?php
	return (string) ob_get_clean();
}

/** Contact details aside (Contact & social settings). */
function nl_contact_aside(): string {
	ob_start();
	?>
	<aside class="nl-contact-aside" aria-label="Other ways to reach us">
		<p><strong>Prefer to call?</strong>A partner picks up during office hours.</p>
		<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', nl_opt( 'phone' ) ) ); ?>"><?php echo esc_html( nl_opt( 'phone' ) ); ?></a><br>
		<a href="mailto:<?php echo esc_attr( antispambot( nl_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( nl_opt( 'email' ) ) ); ?></a><br>
		<?php echo esc_html( nl_opt( 'hours' ) ); ?></p>
		<?php if ( count( (array) nl_opt( 'offices', [] ) ) ) : ?>
			<p><a class="nl-textlink" href="#nl-offices"><span>Offices in <?php echo esc_html( implode( ' and ', array_column( (array) nl_opt( 'offices', [] ), 'city' ) ) ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></a></p>
		<?php endif; ?>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/** Guide download and the optional checklist form. Reads the guide fields of $page_id. */
function nl_guide_html( int $page_id ): string {
	$file  = (int) nl_field( 'guide_file', $page_id );
	$state = sanitize_key( $_GET['checklist'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	?>
	<div class="nl-guide">
		<?php if ( $file ) :
			$path = get_attached_file( $file );
			$size = $path && file_exists( $path ) ? size_format( filesize( $path ), 1 ) : '';
			?>
			<a class="nl-btn" href="<?php echo esc_url( wp_get_attachment_url( $file ) ); ?>" download>Download PDF<?php echo $size ? ', ' . esc_html( $size ) : ''; ?></a>
			<p class="nl-guide__note">Immediate download. No email required.</p>
		<?php endif; ?>
		<form class="nl-checklist" id="nl-checklist" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="nl_checklist">
			<input type="hidden" name="return" value="<?php echo esc_url( get_permalink( $page_id ) ); ?>">
			<input type="hidden" name="token" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
			<?php wp_nonce_field( 'nl_checklist', 'nl_nonce' ); ?>
			<p class="nl-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<div class="nl-field<?php echo 'error' === $state ? ' is-invalid' : ''; ?>">
				<label for="nl-cl-email">Optional: email me the printable checklist sheets</label>
				<div class="nl-inline">
					<input id="nl-cl-email" type="email" name="email" autocomplete="email" required<?php echo 'error' === $state ? ' aria-invalid="true" aria-describedby="nl-cl-err"' : ''; ?>>
					<button class="nl-btn nl-btn--ghost" type="submit">Email me</button>
				</div>
				<?php if ( 'error' === $state ) : ?><p class="nl-field__err" id="nl-cl-err">Please enter a valid email address.</p><?php endif; ?>
				<?php if ( 'sent' === $state ) : ?><p class="nl-field__ok" role="status">Sent. Check your inbox for the checklist.</p><?php endif; ?>
			</div>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}
