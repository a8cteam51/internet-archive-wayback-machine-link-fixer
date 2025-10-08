<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array  $step_data The step data.
 * @param Settings $settings The settings object.
 * @param array $post_types The post types.
 * @param string $header The header template.
 * @param string $footer The footer template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sets the type for api keys based on the current environment.
$wlf_invalid_keys = isset( $_POST['wlf_wizard_invalid_keys'] ); // phpcs:ignore
$wlf_access_type  = '' === $settings->get_archive_access_key() || $wlf_invalid_keys
	? 'text'
	: 'password';
$wlf_secret_type  = '' === $settings->get_archive_secret_key() || $wlf_invalid_keys
	? 'text'
	: 'password';

// Get any temp values from the POST request.
$wlf_existing_access_key = isset( $_POST['wlf_wizard_archive_access_key_temp'] ) ? sanitize_text_field( wp_unslash( $_POST['wlf_wizard_archive_access_key_temp'] ) ) : $settings->get_archive_access_key(); // phpcs:ignore
$wlf_existing_secret_key = isset( $_POST['wlf_wizard_archive_secret_key_temp'] ) ? sanitize_text_field( wp_unslash( $_POST['wlf_wizard_archive_secret_key_temp'] ) ) : $settings->get_archive_secret_key(); // phpcs:ignore
?>

<?php echo wp_kses_post( $header ); ?>

<div class="wlf-wizard__content__header">
	<h2><?php esc_html_e( 'Step 1: Configure the Wayback Machine API', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p><?php esc_html_e( 'To archive more than 4000 links from your site to the Wayback Machine per day, you\'ll need a free archive.org account. Once you have your account, enter the API Access Key and Secret Key below.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		<br /><a href="https://archive.org/account/s3.php" target="_blank"><?php esc_html_e( 'Get your API keys here.', 'internet-archive-wayback-machine-link-fixer' ); ?></a></p>
</div>

<div class="wlf-wizard__content__field">
	<label for="wlf_wizard_archive_access_key">
		<?php esc_html_e( 'Archive.org API Access Key', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<input type="<?php echo esc_html( $wlf_access_type ); ?>" name="wlf_wizard_archive_access_key" value="<?php echo esc_attr( $wlf_existing_access_key ); ?>"<?php echo $wlf_invalid_keys ? ' class="invalid"' : ''; ?>/>
</div>
<div class="wlf-wizard__content__field">
	<label for="wlf_wizard_archive_secret_key">
		<?php esc_html_e( 'Archive.org API Secret Key', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<input type="<?php echo esc_html( $wlf_secret_type ); ?>" name="wlf_wizard_archive_secret_key" value="<?php echo esc_attr( $wlf_existing_secret_key ); ?>"<?php echo $wlf_invalid_keys ? ' class="invalid"' : ''; ?>/>
</div>


<?php echo wp_kses_post( $footer ); ?>
