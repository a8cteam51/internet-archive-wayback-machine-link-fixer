<?php

/**
 * Tests for the Link_Exclusion matcher: the built-in list and the user settings list, merged.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Exclusion
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Exclusion;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

/**
 * Test_Link_Exclusion
 *
 * @group Link
 * @group Link_Exclusion
 */
class Test_Link_Exclusion extends \WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$this->reset();
	}

	public function tear_down(): void {
		$this->reset();
		parent::tear_down();
	}

	private function reset(): void {
		delete_option( Settings::LINK_EXCLUSIONS );
		remove_all_filters( 'iawmlf_link_exclusions' );
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );
	}

	private function is_excluded( string $href ): bool {
		return Link_Exclusion::get_instance()->is_excluded( new Link( $href ) );
	}

	/**
	 * @testdox A built-in (bundled) pattern excludes matching links.
	 *
	 * @return void
	 */
	public function test_bundled_pattern_excludes(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*.domain.tld*' ) );

		$this->assertTrue( $this->is_excluded( 'https://www.domain.tld/x' ) );
		$this->assertTrue( $this->is_excluded( 'https://sub.domain.tld/' ) );
	}

	/**
	 * @testdox Matching is anchored fnmatch: `*.domain.tld*` matches sub-domains but not the bare apex, and a host pattern needs surrounding wildcards to match a full URL.
	 *
	 * @return void
	 */
	public function test_matching_is_anchored_fnmatch(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*.domain.tld*' ) );
		$this->assertFalse( $this->is_excluded( 'https://domain.tld/' ) );

		remove_all_filters( 'iawmlf_bundled_link_exclusions' );
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( 'pics.google.com*' ) );
		$this->assertFalse( $this->is_excluded( 'https://pics.google.com/x' ), 'Needs *pics.google.com* to match a full URL.' );
	}

	/**
	 * @testdox A stored Settings list pattern excludes matching links.
	 *
	 * @return void
	 */
	public function test_settings_option_excludes(): void {
		update_option( Settings::LINK_EXCLUSIONS, array( '*example.org*' ) );

		$this->assertTrue( $this->is_excluded( 'https://example.org/x' ) );
	}

	/**
	 * @testdox The documented iawmlf_link_exclusions filter affects matching.
	 *
	 * @return void
	 */
	public function test_documented_settings_filter_affects_matching(): void {
		add_filter(
			'iawmlf_link_exclusions',
			function ( array $links ): array {
				$links[] = '*example.com*';
				return $links;
			}
		);

		$this->assertTrue( $this->is_excluded( 'https://example.com/page' ) );
	}

	/**
	 * @testdox The built-in list and the settings list are both consulted (merged).
	 *
	 * @return void
	 */
	public function test_built_in_and_settings_are_merged(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*builtin.test*' ) );
		update_option( Settings::LINK_EXCLUSIONS, array( '*settings.test*' ) );

		$this->assertTrue( $this->is_excluded( 'https://builtin.test/a' ) );
		$this->assertTrue( $this->is_excluded( 'https://settings.test/a' ) );
		$this->assertFalse( $this->is_excluded( 'https://neither.test/a' ) );
	}

	/**
	 * @testdox A link matched by both lists stays excluded until it is removed from both (union).
	 *
	 * @return void
	 */
	public function test_union_and_removal(): void {
		$href = 'https://pics.google.com/x';

		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*.google.com*' ) );
		update_option( Settings::LINK_EXCLUSIONS, array( '*pics.google.com*' ) );
		$this->assertTrue( $this->is_excluded( $href ) );

		// Remove the built-in list -> still excluded by the settings list.
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );
		$this->assertTrue( $this->is_excluded( $href ) );

		// Remove the settings list too -> no longer excluded.
		update_option( Settings::LINK_EXCLUSIONS, array() );
		$this->assertFalse( $this->is_excluded( $href ) );
	}

	/**
	 * @testdox A link matching no list is not excluded.
	 *
	 * @return void
	 */
	public function test_unmatched_not_excluded(): void {
		$this->assertFalse( $this->is_excluded( 'https://not-listed.example/x' ) );
	}

	/**
	 * @testdox is_globally_excluded is true only for links matched by the built-in list.
	 *
	 * @return void
	 */
	public function test_is_globally_excluded(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*builtin.test*' ) );
		update_option( Settings::LINK_EXCLUSIONS, array( '*settings.test*' ) );

		$exclusion = Link_Exclusion::get_instance();
		$this->assertTrue( $exclusion->is_globally_excluded( new Link( 'https://builtin.test/x' ) ) );
		$this->assertFalse( $exclusion->is_globally_excluded( new Link( 'https://settings.test/x' ) ) );
		$this->assertFalse( $exclusion->is_globally_excluded( new Link( 'https://neither.test/x' ) ) );
	}

	/**
	 * @testdox is_settings_excluded is true only for links matched by the settings list.
	 *
	 * @return void
	 */
	public function test_is_settings_excluded(): void {
		add_filter( 'iawmlf_bundled_link_exclusions', fn(): array => array( '*builtin.test*' ) );
		update_option( Settings::LINK_EXCLUSIONS, array( '*settings.test*' ) );

		$exclusion = Link_Exclusion::get_instance();
		$this->assertTrue( $exclusion->is_settings_excluded( new Link( 'https://settings.test/x' ) ) );
		$this->assertFalse( $exclusion->is_settings_excluded( new Link( 'https://builtin.test/x' ) ) );
		$this->assertFalse( $exclusion->is_settings_excluded( new Link( 'https://neither.test/x' ) ) );
	}
}
