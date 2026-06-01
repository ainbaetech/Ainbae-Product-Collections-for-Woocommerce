<?php
/**
 * Registers the product_collection custom taxonomy.
 *
 * show_in_menu      = false → prevents auto double menu entry under Products.
 * show_admin_column = false → prevents auto duplicate column in Products list.
 * Both are added manually by AinbaeCFWoo_Collections_Admin exactly once.
 *
 * @package AinbaeCFWoo\Collections
 */

defined( 'ABSPATH' ) || exit;

class AinbaeCFWoo_Collections_Taxonomy {

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
	 * Register the taxonomy — idempotent.
	 */
	public function register(): void {
		if ( taxonomy_exists( AINBAECFWOO_COL_TAXONOMY ) ) {
			return;
		}

		register_taxonomy(
			AINBAECFWOO_COL_TAXONOMY,
			array( 'product' ),
			$this->taxonomy_args()
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function taxonomy_args(): array {
		return array(
			'hierarchical'       => true,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,   // Prevents auto double menu entry.
			'show_in_nav_menus'  => true,
			'show_admin_column'  => false,   // Prevents auto duplicate column.
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'         => AINBAECFWOO_COL_SLUG,
				'with_front'   => false,
				'hierarchical' => true,
			),
			'capabilities'       => array(
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'edit_product_terms',
				'delete_terms' => 'delete_product_terms',
				'assign_terms' => 'assign_product_terms',
			),
			'labels'             => $this->taxonomy_labels(),
		);
	}

	/**
	 * @return array<string,string|null>
	 */
	private function taxonomy_labels(): array {
		return array(
			'name'                       => _x( 'Collections', 'taxonomy general name', 'ainbae-product-collections-for-woocommerce' ),
			'singular_name'              => _x( 'Collection', 'taxonomy singular name', 'ainbae-product-collections-for-woocommerce' ),
			'menu_name'                  => __( 'Collections', 'ainbae-product-collections-for-woocommerce' ),
			'all_items'                  => __( 'All Collections', 'ainbae-product-collections-for-woocommerce' ),
			'edit_item'                  => __( 'Edit Collection', 'ainbae-product-collections-for-woocommerce' ),
			'view_item'                  => __( 'View Collection', 'ainbae-product-collections-for-woocommerce' ),
			'update_item'                => __( 'Update Collection', 'ainbae-product-collections-for-woocommerce' ),
			'add_new_item'               => __( 'Add New Collection', 'ainbae-product-collections-for-woocommerce' ),
			'new_item_name'              => __( 'New Collection Name', 'ainbae-product-collections-for-woocommerce' ),
			'parent_item'                => __( 'Parent Collection', 'ainbae-product-collections-for-woocommerce' ),
			'parent_item_colon'          => __( 'Parent Collection:', 'ainbae-product-collections-for-woocommerce' ),
			'search_items'               => __( 'Search Collections', 'ainbae-product-collections-for-woocommerce' ),
			'popular_items'              => null,
			'separate_items_with_commas' => null,
			'add_or_remove_items'        => null,
			'choose_from_most_used'      => null,
			'not_found'                  => __( 'No collections found.', 'ainbae-product-collections-for-woocommerce' ),
			'no_terms'                   => __( 'No collections', 'ainbae-product-collections-for-woocommerce' ),
			'items_list_navigation'      => __( 'Collections list navigation', 'ainbae-product-collections-for-woocommerce' ),
			'items_list'                 => __( 'Collections list', 'ainbae-product-collections-for-woocommerce' ),
			'back_to_items'              => __( '&larr; Go to Collections', 'ainbae-product-collections-for-woocommerce' ),
		);
	}
}
