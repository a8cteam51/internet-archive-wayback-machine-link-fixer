<?php

/**
 * Tests for the Report Page.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page;

/**
 * Test_Report_Page
 *
 * @group Dashboard
 * @group Report_Page
 */
class Test_Report_Page extends \WP_UnitTestCase {

	private $link_repository;

	/**
	 * Setup
	 */
	public function set_up(): void {
		$this->link_repository = new Link_Repository();
		$GLOBALS['wpdb']->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );

		parent::set_up();
	}

	/**
	 * Tear down
	 */
	public function tear_down(): void {
		remove_all_filters( 'iawmlf_link_details_updated_redirect_param' );
		remove_all_filters( 'wp_redirect' );
		unset( $_POST['iawmlf_link_details_nonce'], $_POST['iawmlf_link_id'], $_REQUEST['iawmlf_link_details_nonce'] );

		parent::tear_down();
	}

	/**
	 * Submit the link details form and capture the redirect location.
	 *
	 * @param integer $link_id The link id to submit.
	 *
	 * @return string|null The redirect location, null if no redirect fired.
	 */
	private function submit_link_details_form( int $link_id ): ?string {
		$_POST['iawmlf_link_details_nonce']    = wp_create_nonce( 'iawmlf_link_details' );
		$_POST['iawmlf_link_id']               = (string) $link_id;
		$_REQUEST['iawmlf_link_details_nonce'] = $_POST['iawmlf_link_details_nonce'];

		$location = null;

		// Capture the redirect and throw before the exit is reached.
		add_filter(
			'wp_redirect',
			function ( $redirect_location ) use ( &$location ) {
				$location = $redirect_location;
				throw new \Exception( 'redirect-captured' );
			}
		);

		try {
			( new Report_Page() )->handle_link_details_form();
		} catch ( \Exception $e ) {
			$this->assertSame( 'redirect-captured', $e->getMessage() );
		}

		return $location;
	}

	/**
	 * @testdox The recognised falsy keywords ('0', 'false', 'no') from the redirect-param filter should suppress the updated flag.
	 *
	 * @dataProvider provider_falsy_keywords
	 *
	 * @param string $filter_return The value the filter returns.
	 *
	 * @return void
	 */
	public function test_falsy_keyword_suppresses_updated_flag( string $filter_return ): void {
		$link = $this->link_repository->upsert( new Link( 'https://example.com/details' ) );

		add_filter( 'iawmlf_link_details_updated_redirect_param', fn() => $filter_return );

		$location = $this->submit_link_details_form( $link->get_id() );

		$this->assertNotNull( $location );
		$this->assertStringNotContainsString( 'iawmlf_updated', $location );
	}

	/**
	 * Falsy keyword provider.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_falsy_keywords(): array {
		return array(
			'zero'   => array( '0' ),
			'false'  => array( 'false' ),
			'no'     => array( 'no' ),
			'empty'  => array( '' ),
		);
	}

	/**
	 * @testdox A truthy non-keyword filter return (documented (bool) contract) should keep the updated flag.
	 *
	 * @return void
	 */
	public function test_truthy_non_keyword_keeps_updated_flag(): void {
		$link = $this->link_repository->upsert( new Link( 'https://example.com/details' ) );

		add_filter( 'iawmlf_link_details_updated_redirect_param', fn() => 'updated' );

		$location = $this->submit_link_details_form( $link->get_id() );

		$this->assertNotNull( $location );
		$this->assertStringContainsString( 'iawmlf_updated=1', $location );
	}

	/**
	 * @testdox A before-saving filter callback that forgets to return the link should not fatal the form save.
	 *
	 * @return void
	 */
	public function test_before_saving_filter_returning_null_does_not_fatal(): void {
		$link = $this->link_repository->upsert( new Link( 'https://example.com/details' ) );

		// A broken third-party callback: mutates but returns nothing.
		add_filter( 'iawmlf_before_saving_link_details', fn() => null );

		$location = $this->submit_link_details_form( $link->get_id() );

		remove_all_filters( 'iawmlf_before_saving_link_details' );

		$this->assertNotNull( $location, 'The save should complete and redirect despite the malformed filter.' );
		$this->assertStringContainsString( 'iawmlf_updated=1', $location );
	}

	/**
	 * @testdox A manual exclusion must carry the user marker even when the link already had a system message, so background events cannot lift it. (S053)
	 *
	 * @return void
	 */
	public function test_manual_exclusion_with_existing_message_is_marked_as_manual(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// A link with a leftover system message, not yet excluded.
		$link = new Link( 'https://example.com/s053-existing-message' );
		$link->set_message( 'Redirected. SomeSystemError' );
		$link = $this->link_repository->upsert( $link );

		$_POST['iawmlf_exclude_link'] = '1';
		$this->submit_link_details_form( $link->get_id() );
		unset( $_POST['iawmlf_exclude_link'] );

		$saved = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertTrue( $saved->is_excluded() );
		$this->assertTrue( $saved->is_manual_exclusion(), 'The exclusion must be recognised as manual despite the pre-existing message.' );
	}

	/**
	 * @testdox Saving the details form of a pattern-excluded link must not flip its database exclusion flag. (S054)
	 *
	 * @return void
	 */
	public function test_saving_pattern_excluded_link_does_not_set_db_flag(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$link = $this->link_repository->upsert( new Link( 'https://pattern-excluded.example.com/s054' ) );

		// The template renders a hidden iawmlf_exclude_link carrying the DB flag - empty when not excluded.
		$_POST['iawmlf_exclude_link'] = '';
		$this->submit_link_details_form( $link->get_id() );
		unset( $_POST['iawmlf_exclude_link'] );

		$saved = $this->link_repository->find_by_id( $link->get_id() );

		$this->assertFalse( $saved->is_excluded(), 'Saving the form must not flip the exclusion flag on.' );
	}

	/**
	 * @testdox Bulk action processing must be hooked to load-{hook}, before any output. (S056)
	 *
	 * @return void
	 */
	public function test_bulk_actions_are_processed_on_load_hook(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$page = new Report_Page();
		$page->register_page();

		$hook = get_plugin_page_hookname( 'iawmlf-links', \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page::DASHBOARD_SLUG );

		$this->assertNotFalse( has_action( "load-$hook", array( $page, 'handle_bulk_actions' ) ) );
	}

	/**
	 * @testdox The default filter value should include the updated flag.
	 *
	 * @return void
	 */
	public function test_default_includes_updated_flag(): void {
		$link = $this->link_repository->upsert( new Link( 'https://example.com/details' ) );

		$location = $this->submit_link_details_form( $link->get_id() );

		$this->assertNotNull( $location );
		$this->assertStringContainsString( 'iawmlf_updated=1', $location );
	}
}
