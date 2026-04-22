<?php
/**
 * Frontend functionality — mirrors product_cat archive behaviour exactly.
 *
 * Responsibilities:
 *  - Correct WP_Query for collection archive pages.
 *  - Serve WooCommerce's archive-product.php template (with theme override support).
 *  - Page title, description, breadcrumbs.
 *  - Ensure WooCommerce loop hooks fire (sorting, pagination, product grid).
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Frontend {

	/** @var self|null */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		// ── Query ─────────────────────────────────────────────────────────────
		add_action( 'pre_get_posts', [ $this, 'fix_archive_query' ], 10 );

		// ── Template ──────────────────────────────────────────────────────────
		// WooCommerce's own template_include already handles taxonomies registered
		// for products (because get_object_taxonomies('product') includes ours).
		// We add a fallback just in case.
		add_filter( 'template_include', [ $this, 'archive_template_fallback' ], 20 );

		// ── Tell WooCommerce this IS a product archive ─────────────────────────
		add_filter( 'woocommerce_is_product_archive', [ $this, 'is_product_archive' ] );

		// ── Page title ────────────────────────────────────────────────────────
		add_filter( 'woocommerce_page_title',              [ $this, 'archive_page_title' ] );
		add_filter( 'woocommerce_taxonomy_archive_description', [ $this, 'archive_description' ] );

		// ── Breadcrumbs ───────────────────────────────────────────────────────
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'add_breadcrumbs' ], 10, 2 );

		// ── Body class ────────────────────────────────────────────────────────
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );

		// ── <title> tag ───────────────────────────────────────────────────────
		add_filter( 'wp_title',          [ $this, 'wp_title' ], 10, 2 );
		add_filter( 'document_title_parts', [ $this, 'document_title' ] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Query
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Ensure the main query on collection archive pages returns WooCommerce products.
	 * Applies the same catalogue visibility restriction as product_cat archives.
	 *
	 * @param \WP_Query $query The main query object.
	 */
	public function fix_archive_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return;
		}

		$query->set( 'post_type', 'product' );

		// Products per page — respect the WooCommerce setting.
		$per_page = (int) apply_filters(
			'loop_shop_per_page',
			wc_get_default_products_per_row() * wc_get_default_product_rows_per_page()
		);
		$query->set( 'posts_per_page', $per_page );

		// Exclude products hidden from the catalogue.
		$existing_tax_query = (array) $query->get( 'tax_query' );
		$query->set( 'tax_query', array_merge(
			$existing_tax_query,
			[
				'relation' => 'AND',
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => [ 'exclude-from-catalog' ],
					'operator' => 'NOT IN',
				],
			]
		) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Template
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Fallback template loader if WooCommerce's own loader doesn't catch it.
	 * Lookup order (same as WooCommerce taxonomy templates):
	 *   1. {theme}/taxonomy-product_collection-{slug}.php
	 *   2. {theme}/taxonomy-product_collection.php
	 *   3. {theme}/woocommerce/taxonomy-product_collection.php
	 *   4. WooCommerce built-in archive-product.php
	 *
	 * @param  string $template Currently resolved template.
	 * @return string
	 */
	public function archive_template_fallback( string $template ): string {
		if ( ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return $template;
		}

		$term = get_queried_object();
		$slug = $term instanceof \WP_Term ? $term->slug : '';

		$candidates = array_filter( [
			$slug ? 'taxonomy-' . AINBAE_COL_TAXONOMY . '-' . $slug . '.php' : '',
			'taxonomy-' . AINBAE_COL_TAXONOMY . '.php',
			'woocommerce/taxonomy-' . AINBAE_COL_TAXONOMY . '.php',
		] );

		$theme_tpl = locate_template( array_values( $candidates ) );
		if ( $theme_tpl ) {
			return $theme_tpl;
		}

		// WooCommerce built-in archive template.
		$wc_tpl = WC()->plugin_path() . '/templates/archive-product.php';
		if ( file_exists( $wc_tpl ) ) {
			return $wc_tpl;
		}

		return $template;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  WooCommerce archive integration
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Tell WooCommerce this is a product archive so the loop hooks fire.
	 *
	 * @param  bool $is_archive
	 * @return bool
	 */
	public function is_product_archive( bool $is_archive ): bool {
		return $is_archive || is_tax( AINBAE_COL_TAXONOMY );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Page title & description
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Replace the WooCommerce page title with the collection name.
	 *
	 * @param  string $title
	 * @return string
	 */
	public function archive_page_title( string $title ): string {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			return $term instanceof \WP_Term ? $term->name : $title;
		}
		return $title;
	}

	/**
	 * Output the collection description below the page title.
	 *
	 * @return string
	 */
	public function archive_description(): string {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term && ! empty( $term->description ) ) {
				return '<div class="term-description">' . wp_kses_post( wpautop( wptexturize( $term->description ) ) ) . '</div>';
			}
		}
		return '';
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Breadcrumbs
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Build WooCommerce breadcrumbs for collection archives, including ancestors.
	 *
	 * @param  array $crumbs     Current breadcrumb array  [ [ name, url ], … ]
	 * @param  mixed $breadcrumb WooCommerce breadcrumb object.
	 * @return array
	 */
	public function add_breadcrumbs( array $crumbs, $breadcrumb ): array {
		if ( ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return $crumbs;
		}

		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return $crumbs;
		}

		// Remove the current-page crumb (last element) — we rebuild it below.
		array_pop( $crumbs );

		// Walk up the ancestor tree (oldest ancestor first).
		$ancestors = array_reverse( get_ancestors( $term->term_id, AINBAE_COL_TAXONOMY ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, AINBAE_COL_TAXONOMY );
			if ( $ancestor instanceof \WP_Term ) {
				$crumbs[] = [ $ancestor->name, get_term_link( $ancestor ) ];
			}
		}

		// Current collection (no link — it's the active page).
		$crumbs[] = [ $term->name, get_term_link( $term ) ];

		return $crumbs;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Body class & <title>
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Add descriptive body classes on collection archive pages.
	 *
	 * @param  string[] $classes
	 * @return string[]
	 */
	public function add_body_classes( array $classes ): array {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term      = get_queried_object();
			$classes[] = 'collection-archive';
			if ( $term instanceof \WP_Term ) {
				$classes[] = 'collection-' . sanitize_html_class( $term->slug );
			}
		}
		return $classes;
	}

	/**
	 * wp_title filter (classic themes).
	 *
	 * @param  string $title Current title.
	 * @param  string $sep   Separator character.
	 * @return string
	 */
	public function wp_title( string $title, string $sep ): string {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				return $term->name . " $sep " . get_bloginfo( 'name' );
			}
		}
		return $title;
	}

	/**
	 * document_title_parts filter (block themes / wp_get_document_title).
	 *
	 * @param  array<string,string> $parts
	 * @return array<string,string>
	 */
	public function document_title( array $parts ): array {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$parts['title'] = $term->name;
			}
		}
		return $parts;
	}
}
