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

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

// Holds the class to hide all inputs if not enabled.
$iawmlf_hide_class = Settings::is_link_processing_enabled() ? '' : ' disabled';
?>

<?php echo wp_kses( $header, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>

<div class="iawmlf-wizard__content__header">
	<h2><?php esc_html_e( 'Step 2: Configure the Link Fixer', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'You can set the Link Fixer to work only with specific post types and apply it to existing posts.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>

<div class="iawmlf-wizard__content__field">
	<div class="iawmlf-wizard__content__inner-field checkbox">
		<div class="inner-spaced-between">
			<label for="iawmlf_wizard_activate_link_fixer">
				<?php esc_html_e( 'Enable Link Fixer', 'internet-archive-wayback-machine-link-fixer' ); ?>
			</label>
			<input type="checkbox" id="is_active" name="iawmlf_wizard_activate_link_fixer" value="1" <?php checked( Settings::is_link_processing_enabled() ); ?> />
		</div>
		<p class="description"><?php esc_html_e( 'When enabled, all links within your selected post types will be processed for potential archiving by the Link Fixer.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	</div>
</div>

<div class="iawmlf-wizard__content__field is_optional <?php echo esc_attr( $iawmlf_hide_class ); ?>" >
	<label for="iawmlf_wizard_post_types">
		<?php esc_html_e( 'Post Types', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Select the post types you want to enable the link fixer for.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<div class="iawmlf-wizard__content__inner-field checkboxes">
		<?php foreach ( $post_types as $iawmlf_pt_slug => $iawmlf_pt_name ) : ?>
			<div class="inner-spaced-between__list">
				<label>
					<?php echo esc_html( $iawmlf_pt_name ); ?>
				</label>
				<input type="checkbox" name="iawmlf_wizard_post_types[]" value="<?php echo esc_attr( $iawmlf_pt_slug ); ?>" <?php checked( in_array( $iawmlf_pt_slug, Settings::get_allowed_post_types(), true ) ); ?> />
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="iawmlf-wizard__content__field  is_optional <?php echo esc_attr( $iawmlf_hide_class ); ?>">
	<div class="iawmlf-wizard__content__inner-field checkbox">
		<div class="inner-spaced-between">
			<label for="iawmlf_wizard_scan_existing_content">
				<?php esc_html_e( 'Scan Existing Content', 'internet-archive-wayback-machine-link-fixer' ); ?>
			</label>
			<input type="checkbox" id="iawmlf_is_active" name="iawmlf_wizard_scan_existing_content" id="iawmlf_wizard_scan_existing_content" value="1" <?php checked( Settings::should_scan_existing_posts() ); ?> />
		</div>
		<p class="description"><?php esc_html_e( 'If enabled, all existing posts of the selected types will be scanned and processed. Please note this process can take significant time (potentially hours or days) depending on the amount of content and number of links.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	</div>
</div>

<div class="iawmlf-wizard__content__field is_optional <?php echo esc_attr( $iawmlf_hide_class ); ?>" >
	<label for="iawmlf_wizard_outcome">
		<?php esc_html_e( 'Action for Broken Links', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'What should the Link Fixer do when it identifies a broken link?', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<select
		id="iawmlf_wizard_outcome"
		name="iawmlf_wizard_outcome"
	>
		<option value="<?php echo esc_attr( Settings::FIXER_OPTION_REPLACE_LINK ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_REPLACE_LINK ); ?>>
			<?php esc_html_e( 'Replace Link (No Notification)', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</option>
		<option value="<?php echo esc_attr( Settings::FIXER_OPTION_DO_NOTHING ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_DO_NOTHING ); ?>>
			<?php esc_html_e( 'Do Nothing', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</option>
	</select>
</div>

<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
