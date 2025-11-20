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

defined( 'ABSPATH' ) || exit;

?>

<?php echo wp_kses( $header, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>

<div class="iawmlf-wizard__content__header">
	<h2><?php esc_html_e( '??Step 1: Configure the Wayback Machine API', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( '??To archive more than 4000 links from your site to the Wayback Machine per day, you\'ll need a free archive.org account. Once you have your account, enter the API Access Key and Secret Key below. (Optional)', 'internet-archive-wayback-machine-link-fixer' ); ?>
		<br /><a href="https://archive.org/account/s3.php" target="_blank"><?php esc_html_e( 'Get your API keys here.', 'internet-archive-wayback-machine-link-fixer' ); ?></a></p>
</div>



<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
