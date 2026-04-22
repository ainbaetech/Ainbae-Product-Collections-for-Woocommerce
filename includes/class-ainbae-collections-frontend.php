<?php
/**
 * Frontend functionality — mirrors product_cat archive behaviour exactly.
 *
 * FIX #3 — Collection archive pages now use the SAME template as Product
 *           Categories (taxonomy-product_cat.php → archive-product.php),
 *           so they inherit exactly the same layout, sidebar behaviour, and
 *           CSS as the category pages. No custom sidebar will appear unless
 *           the category pages also have one. The body_class filter adds
 *           'woocommerce', 'woocommerce-page', and 'tax-product_cat' so that
 *           all theme and WooCommerce CSS rules that apply to category pages
 *           also apply to collection pages.
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

		// ── Template (FIX #3) ─────────────────────────────────────────────────
		// Priority 20 — runs AFTER WooCommerce's own loader (@10) so we can
		// inspect what it resolved and replace non-WooCommerce-aware templates.
		add_filter( 'template_include', [ $this, 'use_product_cat_template' ], 20 );

		// ── Tell WooCommerce this IS a product archive ─────────────────────────
		add_filter( 'woocommerce_is_product_archive', [ $this, 'is_product_archive' ] );

		// ── Page title & description ───────────────────────────────────────────
		add_filter( 'woocommerce_page_title',               [ $this, 'archive_page_title' ] );
		add_filter( 'woocommerce_taxonomy_archive_description', [ $this, 'archive_description' ] );

		// ── Breadcrumbs ────────────────────────────────────────────────────────
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'add_breadcrumbs' ], 10, 2 );

		// ── Body classes (FIX #3) ─────────────────────────────────────────────
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );

		// ── <title> tag ────────────────────────────────────────────────────────
		add_filter( 'wp_title',          [ $this, 'wp_title' ], 10, 2 );
		add_filter( 'document_title_parts', [ $this, 'document_title' ] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Query
	// ══════════════════════════════════════════════════════════════════════════

	public function fix_archive_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return;
		}

		$query->set( 'post_type', 'product' );

		$per_page = (int) apply_filters(
			'loop_shop_per_page',
			wc_get_default_products_per_row() * wc_get_default_product_rows_per_page()
		);
		$query->set( 'posts_per_page', $per_page );

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
	//  FIX #3 — Template: always use the product_cat template so the layout
	//            (sidebar, columns, CSS) is identical to category pages.
	//
	//  Lookup order (same priority as WooCommerce uses for product_cat):
	//   1. {child-theme}/woocommerce/taxonomy-product_cat-{slug}.php
	//   2. {child-theme}/woocommerce/taxonomy-product_cat.php
	//   3. {parent-theme}/woocommerce/taxonomy-product_cat.php
	//   4. {child-theme}/woocommerce/archive-product.php
	//   5. {parent-theme}/woocommerce/archive-product.php
	//   6. WooCommerce built-in: /templates/archive-product.php
	// ══════════════════════════════════════════════════════════════════════════

	public function use_product_cat_template( string $template ): string {
		if ( ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return $template;
		}

		$term = get_queried_object();
		$slug = $term instanceof \WP_Term ? '-' . $term->slug : '';

		$child_wc_dir  = get_stylesheet_directory() . '/woocommerce/';
		$parent_wc_dir = get_template_directory() . '/woocommerce/';
		$wc_tpl_dir    = WC()->plugin_path() . '/templates/';

		$candidates = array_filter( [
			// Slug-specific product_cat template (child theme)
			$slug ? $child_wc_dir . 'taxonomy-product_cat' . $slug . '.php' : '',
			// Generic product_cat template (child theme)
			$child_wc_dir . 'taxonomy-product_cat.php',
			// Generic product_cat template (parent theme)
			$parent_wc_dir . 'taxonomy-product_cat.php',
			// Fallback archive (child theme)
			$child_wc_dir . 'archive-product.php',
			// Fallback archive (parent theme)
			$parent_wc_dir . 'archive-product.php',
			// WooCommerce built-in archive
			$wc_tpl_dir . 'archive-product.php',
		] );

		foreach ( $candidates as $candidate ) {
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return $template;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  WooCommerce archive integration
	// ══════════════════════════════════════════════════════════════════════════

	public function is_product_archive( bool $is_archive ): bool {
		return $is_archive || is_tax( AINBAE_COL_TAXONOMY );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Page title & description
	// ══════════════════════════════════════════════════════════════════════════

	public function archive_page_title( string $title ): string {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			return $term instanceof \WP_Term ? $term->name : $title;
		}
		return $title;
	}

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

	public function add_breadcrumbs( array $crumbs, $breadcrumb ): array {
		if ( ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return $crumbs;
		}

		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return $crumbs;
		}

		array_pop( $crumbs );

		// If a Collections page exists, add it as a breadcrumb parent.
		$page_id = (int) get_option( AINBAE_COL_PAGE_OPTION, 0 );
		if ( $page_id && get_post( $page_id ) ) {
			$crumbs[] = [ get_the_title( $page_id ), get_permalink( $page_id ) ];
		}

		$ancestors = array_reverse( get_ancestors( $term->term_id, AINBAE_COL_TAXONOMY ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, AINBAE_COL_TAXONOMY );
			if ( $ancestor instanceof \WP_Term ) {
				$crumbs[] = [ $ancestor->name, get_term_link( $ancestor ) ];
			}
		}

		$crumbs[] = [ $term->name, get_term_link( $term ) ];

		return $crumbs;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  FIX #3 — Body classes: add WooCommerce + product_cat classes so the
	//            theme applies the exact same CSS/layout as category pages.
	// ══════════════════════════════════════════════════════════════════════════

	public function add_body_classes( array $classes ): array {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();

			// Core WooCommerce classes (so woocommerce.css rules apply).
			$classes[] = 'woocommerce';
			$classes[] = 'woocommerce-page';

			// Make the theme treat this page like a product category page.
			$classes[] = 'tax-product_cat';

			// Descriptive classes for custom styling if needed.
			$classes[] = 'tax-' . AINBAE_COL_TAXONOMY;
			$classes[] = 'collection-archive';
			if ( $term instanceof \WP_Term ) {
				$classes[] = 'collection-' . sanitize_html_class( $term->slug );
			}
		}
		return $classes;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  <title> tag
	// ══════════════════════════════════════════════════════════════════════════

	public function wp_title( string $title, string $sep ): string {
		if ( is_tax( AINBAE_COL_TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				return $term->name . " $sep " . get_bloginfo( 'name' );
			}
		}
		return $title;
	}

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
