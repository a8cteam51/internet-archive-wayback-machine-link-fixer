<?php
/**
 * Template for rendering the details of a link within a report.
 *
 * @since 1.2.0
 *
 * @var WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link $wlf_link The link.
 * @var \WP_Post[] $wlf_posts The posts that contain the link.
 * @var string $wlf_back_url The URL to return to the report.
 */
?>
<div class="wrap">
	<a href="<?php echo esc_url( $wlf_back_url ); ?>" class="page-title">Back to report</a>

	<p>Link URL - <?php echo esc_html( $wlf_link->get_href() ); ?></p>
	<p>Archived URL - <?php echo esc_html( $wlf_link->get_archived_href() ); ?></p>

	<p>Is the link broken? <b><?php echo esc_html( $wlf_link->is_broken() ? 'Yes' : 'No' ); ?></b></p>

	<h2>Checks:</h2>
	<ul>
		<?php foreach ( $wlf_link->get_checks() as $wlf_check ) : ?>
			<li>
				<p>Date : <?php echo esc_html( \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', esc_attr( $wlf_check['date'] ) )->format( get_option( 'date_format' ) ) ); ?>
				</br>Status : <?php echo esc_html( $wlf_check['http_code'] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<h2>Posts Link Used In:</h2>
	<?php if ( empty( $wlf_posts ) ) : ?>
		<p>No posts found for this link.</p>
	<?php else : ?>

		<ul>
			<?php foreach ( $wlf_posts as $wlf_post ) : ?>
				<li>
					<a href="<?php echo esc_url( get_edit_post_link( $wlf_post->ID ) ); ?>">
						<?php echo esc_html( $wlf_post->post_title ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>


</div>


