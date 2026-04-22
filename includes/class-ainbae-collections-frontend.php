<?php
/**
 * Frontend functionality.
 *
 * ISSUE #1 FIX — Layout identical to Product Categories:
 *
 *   The root problem is that every WooCommerce-aware theme uses
 *   is_product_category() (which calls is_tax('product_cat')) to decide
 *   sidebar width, column count, and CSS class. Our custom taxonomy returns
 *   false for that check, so themes apply a different layout.
 *
 *   Solution: in template_redirect (priority 5, before theme code fires at 10)
 *   we temporarily change $wp_query->queried_object->taxonomy to 'product_cat'.
 *   This makes is_product_category() return TRUE for the entire page render,
 *   so the theme applies exactly the same layout it does for category pages.
 *
 *   We store a static flag ($is_collection_archive) set during pre_get_posts
 *   so our own internal checks still work after the spoof changes is_tax().
 *
 *   We also filter term_link so any in-page links still point to /collection/…
 *   rather than the spoofed /product-category/… URL.
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Frontend {

	/** @var self|null */
	private static ?self $instance = null;

	/**
	 * Set to true the moment pre_get_posts detects we're on a collection archive.
	 * Use self::is_collection() for all internal checks instead of is_tax().
	 *
	 * @var bool
	 */
	private static bool $is_collection_archive = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		// Query — runs before template_redirect, sets our flag.
		add_action( 'pre_get_posts', array( $this, 'fix_archive_query' ), 10 );

		// Issue #1 — Spoof queried_object->taxonomy before the theme loads.
		add_action( 'template_redirect', array( $this, 'spoof_product_cat_taxonomy' ), 5 );

		// Issue #1 — Fix term_link so /collection/ URLs stay correct after spoof.
		add_filter( 'term_link', array( $this, 'fix_term_link' ), 10, 3 );

		// Tell WooCommerce this IS a product archive (hooks run with our flag).
		add_filter( 'woocommerce_is_product_archive', array( $this, 'is_product_archive' ) );

		// Page title & description.
		add_filter( 'woocommerce_page_title',                    array( $this, 'archive_page_title' ) );
		add_filter( 'woocommerce_taxonomy_archive_description',  array( $this, 'archive_description' ) );

		// Breadcrumbs.
		add_filter( 'woocommerce_get_breadcrumb', array( $this, 'add_breadcrumbs' ), 10, 2 );

		// Body classes.
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		// <title> tag.
		add_filter( 'wp_title',             array( $this, 'wp_title' ), 10, 2 );
		add_filter( 'document_title_parts', array( $this, 'document_title' ) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Helper — is the current page a collection archive?
	//  Use this everywhere instead of is_tax( AINBAE_COL_TAXONOMY ).
	// ══════════════════════════════════════════════════════════════════════════

	public static function is_collection(): bool {
		// After spoof, is_tax(AINBAE_COL_TAXONOMY) returns false, so we rely on flag.
		return self::$is_collection_archive || is_tax( AINBAE_COL_TAXONOMY );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Query
	// ══════════════════════════════════════════════════════════════════════════

	public function fix_archive_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! is_tax( AINBAE_COL_TAXONOMY ) ) {
			return;
		}

		// Store flag before spoof changes is_tax() result.
		self::$is_collection_archive = true;

		$query->set( 'post_type', 'product' );

		// Use WooCommerce default per-page without creating an unprefixed filter.
		$per_page = wc_get_default_products_per_row() * wc_get_default_product_rows_per_page();
		$query->set( 'posts_per_page', max( 1, (int) $per_page ) );

		// Exclude catalogue-hidden products.
		$existing = (array) $query->get( 'tax_query' );
		$query->set( 'tax_query', array_merge(
			$existing,
			array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			)
		) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  ISSUE #1 — Spoof queried_object->taxonomy = 'product_cat'
	//
	//  Priority 5 fires BEFORE:
	//    - Theme's template_redirect @ 10 (Astra, OceanWP, Flatsome, etc.)
	//    - WooCommerce's template_redirect @ 10
	//
	//  After this runs, is_product_category() returns TRUE for the whole request,
	//  so themes use their product_cat layout automatically.
	// ══════════════════════════════════════════════════════════════════════════

	public function spoof_product_cat_taxonomy(): void {
		if ( ! self::$is_collection_archive ) {
			return;
		}

		global $wp_query;

		if ( ! isset( $wp_query->queried_object ) || ! ( $wp_query->queried_object instanceof \WP_Term ) ) {
			return;
		}

		// Store original taxonomy on the object so term_link filter can restore it.
		$wp_query->queried_object->ainbae_real_taxonomy    = $wp_query->queried_object->taxonomy;
		$wp_query->queried_object->ainbae_real_slug        = $wp_query->queried_object->slug;

		// Change taxonomy to product_cat → makes is_product_category() return TRUE.
		$wp_query->queried_object->taxonomy = 'product_cat';
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  ISSUE #1 — Fix term_link so URLs still point to /collection/…
	//
	//  After the spoof, get_term_link() would generate /product-category/…
	//  because WordPress uses the object's taxonomy property for rewrite rules.
	//  We detect spoofed terms and regenerate the correct /collection/… URL.
	// ══════════════════════════════════════════════════════════════════════════

	public function fix_term_link( string $url, \WP_Term $term, string $taxonomy ): string {
		// Only correct links for spoofed collection terms.
		if ( 'product_cat' !== $taxonomy || ! isset( $term->ainbae_real_taxonomy ) ) {
			return $url;
		}

		// Temporarily restore real taxonomy to generate the correct URL,
		// then put the spoof back so is_product_category() keeps working.
		$term->taxonomy = $term->ainbae_real_taxonomy;
		$correct_url    = get_term_link( $term->term_id, $term->ainbae_real_taxonomy );
		$term->taxonomy = 'product_cat';  // Restore spoof.

		return is_wp_error( $correct_url ) ? $url : $correct_url;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  WooCommerce archive integration
	// ══════════════════════════════════════════════════════════════════════════

	public function is_product_archive( bool $is_archive ): bool {
		return $is_archive || self::is_collection();
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Page title & description
	// ══════════════════════════════════════════════════════════════════════════

	public function archive_page_title( string $title ): string {
		if ( self::is_collection() ) {
			$term = get_queried_object();
			return $term instanceof \WP_Term ? $term->name : $title;
		}
		return $title;
	}

	public function archive_description(): string {
		if ( self::is_collection() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term && ! empty( $term->description ) ) {
				return '<div class="term-description">' . wp_kses_post( wpautop( wptexturize( $term->description ) ) ) . '</div>';
			}
		}
		return '';
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Breadcrumbs — use term_id + real taxonomy to avoid spoofed URLs.
	// ══════════════════════════════════════════════════════════════════════════

	public function add_breadcrumbs( array $crumbs, $breadcrumb ): array {
		if ( ! self::is_collection() ) {
			return $crumbs;
		}

		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return $crumbs;
		}

		array_pop( $crumbs );

		// Link to the Collections landing page if one is set.
		$page_id = (int) get_option( AINBAE_COL_PAGE_OPTION, 0 );
		if ( $page_id && get_post( $page_id ) ) {
			$crumbs[] = array( get_the_title( $page_id ), get_permalink( $page_id ) );
		}

		// Ancestor crumbs — always use real taxonomy for correct URLs.
		$ancestors = array_reverse( get_ancestors( $term->term_id, AINBAE_COL_TAXONOMY ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, AINBAE_COL_TAXONOMY );
			if ( $ancestor instanceof \WP_Term ) {
				$link     = get_term_link( $ancestor->term_id, AINBAE_COL_TAXONOMY );
				$crumbs[] = array( $ancestor->name, is_wp_error( $link ) ? '' : $link );
			}
		}

		// Current term — no link (active page).
		$current_link = get_term_link( $term->term_id, AINBAE_COL_TAXONOMY );
		$crumbs[]     = array( $term->name, is_wp_error( $current_link ) ? '' : $current_link );

		return $crumbs;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Body classes.
	// ══════════════════════════════════════════════════════════════════════════

	public function add_body_classes( array $classes ): array {
		if ( self::is_collection() ) {
			$term = get_queried_object();

			// Core WooCommerce classes so woocommerce.css rules apply.
			$classes[] = 'woocommerce';
			$classes[] = 'woocommerce-page';

			// Extra descriptive classes.
			$classes[] = 'tax-' . sanitize_html_class( AINBAE_COL_TAXONOMY );
			$classes[] = 'collection-archive';

			if ( $term instanceof \WP_Term ) {
				$classes[] = 'collection-' . sanitize_html_class( $term->slug );
			}
		}
		return $classes;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  <title> tag.
	// ══════════════════════════════════════════════════════════════════════════

	public function wp_title( string $title, string $sep ): string {
		if ( self::is_collection() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				return $term->name . " $sep " . get_bloginfo( 'name' );
			}
		}
		return $title;
	}

	public function document_title( array $parts ): array {
		if ( self::is_collection() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$parts['title'] = $term->name;
			}
		}
		return $parts;
	}
}
