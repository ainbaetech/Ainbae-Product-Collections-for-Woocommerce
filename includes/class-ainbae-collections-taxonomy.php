<?php
/**
 * Registers the product_collection custom taxonomy.
 *
 * FIX #1 — show_in_menu set to FALSE so WordPress does NOT auto-inject a
 *           second "Collections" entry under Products. The Admin class adds
 *           exactly one menu item manually.
 *
 * FIX #2 — show_admin_column set to FALSE so WordPress does NOT auto-inject
 *           a "Product Collections" column. The Admin class adds exactly one
 *           "Collections" column manually.
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Taxonomy {

	/** @var self|null */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Register the taxonomy — safe to call multiple times (idempotent).
	 */
	public function register(): void {
		if ( taxonomy_exists( AINBAE_COL_TAXONOMY ) ) {
			return;
		}

		register_taxonomy(
			AINBAE_COL_TAXONOMY,
			[ 'product' ],
			$this->taxonomy_args()
		);
	}

	/**
	 * Build taxonomy args — mirrors product_cat as closely as possible.
	 *
	 * @return array<string,mixed>
	 */
	private function taxonomy_args(): array {
		return [
			// ── Behaviour ────────────────────────────────────────────────────
			'hierarchical'       => true,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,  // FIX #1: prevents auto double menu entry
			'show_in_nav_menus'  => true,
			'show_admin_column'  => false,  // FIX #2: prevents auto duplicate column
			'show_in_rest'       => true,
			'query_var'          => true,

			// ── URL rewrite ───────────────────────────────────────────────────
			'rewrite' => [
				'slug'         => AINBAE_COL_SLUG,
				'with_front'   => false,
				'hierarchical' => true,
			],

			// ── Capabilities — inherit from WooCommerce product_cat ───────────
			'capabilities' => [
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'edit_product_terms',
				'delete_terms' => 'delete_product_terms',
				'assign_terms' => 'assign_product_terms',
			],

			// ── Labels ────────────────────────────────────────────────────────
			'labels' => $this->taxonomy_labels(),
		];
	}

	/**
	 * Full label set for the taxonomy.
	 *
	 * @return array<string,string|null>
	 */
	private function taxonomy_labels(): array {
		return [
			'name'                       => _x( 'Collections', 'taxonomy general name', 'ainbae-collections' ),
			'singular_name'              => _x( 'Collection', 'taxonomy singular name', 'ainbae-collections' ),
			'menu_name'                  => __( 'Collections', 'ainbae-collections' ),
			'all_items'                  => __( 'All Collections', 'ainbae-collections' ),
			'edit_item'                  => __( 'Edit Collection', 'ainbae-collections' ),
			'view_item'                  => __( 'View Collection', 'ainbae-collections' ),
			'update_item'                => __( 'Update Collection', 'ainbae-collections' ),
			'add_new_item'               => __( 'Add New Collection', 'ainbae-collections' ),
			'new_item_name'              => __( 'New Collection Name', 'ainbae-collections' ),
			'parent_item'                => __( 'Parent Collection', 'ainbae-collections' ),
			'parent_item_colon'          => __( 'Parent Collection:', 'ainbae-collections' ),
			'search_items'               => __( 'Search Collections', 'ainbae-collections' ),
			'popular_items'              => null,
			'separate_items_with_commas' => null,
			'add_or_remove_items'        => null,
			'choose_from_most_used'      => null,
			'not_found'                  => __( 'No collections found.', 'ainbae-collections' ),
			'no_terms'                   => __( 'No collections', 'ainbae-collections' ),
			'items_list_navigation'      => __( 'Collections list navigation', 'ainbae-collections' ),
			'items_list'                 => __( 'Collections list', 'ainbae-collections' ),
			'back_to_items'              => __( '&larr; Go to Collections', 'ainbae-collections' ),
		];
	}
}
