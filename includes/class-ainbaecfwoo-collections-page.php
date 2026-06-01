<?php
/**
 * Collections Landing Page.
 *
 * Provides [ainbaecfwoo_collections] shortcode and conflict-safe page creation.
 * Uses thumbnail_id term meta (same key as WooCommerce product_cat).
 *
 * @package AinbaeCFWoo\Collections
 */

defined( 'ABSPATH' ) || exit;

class AinbaeCFWoo_Collections_Page {

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
		add_shortcode( 'ainbaecfwoo_collections', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles' ) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Shortcode — [ainbaecfwoo_collections columns="3" orderby="name" order="ASC"]
	// ══════════════════════════════════════════════════════════════════════════

	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'columns'    => 3,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => 0,
			'limit'      => -1,
		), $atts, 'ainbaecfwoo_collections' );

		$terms = get_terms( array(
			'taxonomy'   => AINBAECFWOO_COL_TAXONOMY,
			'orderby'    => sanitize_key( $atts['orderby'] ),
			'order'      => strtoupper( $atts['order'] ) === 'DESC' ? 'DESC' : 'ASC',
			'hide_empty' => (int) $atts['hide_empty'],
			'number'     => (int) $atts['limit'] > 0 ? (int) $atts['limit'] : 0,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<p class="ainbaecfwoo-col-empty woocommerce-info">'
				. esc_html__( 'No collections found.', 'ainbae-product-collections-for-woocommerce' )
				. '</p>';
		}

		$cols = max( 1, min( 6, (int) $atts['columns'] ) );

		ob_start();
		?>
		<div class="ainbaecfwoo-col-grid ainbaecfwoo-col-grid-<?php echo esc_attr( $cols ); ?> woocommerce">
			<ul class="products columns-<?php echo esc_attr( $cols ); ?>">
			<?php foreach ( $terms as $term ) :
				// Always use real taxonomy for links — term_id + taxonomy constant.
				$link     = get_term_link( $term->term_id, AINBAECFWOO_COL_TAXONOMY );
				$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$img_src  = $thumb_id
					? wp_get_attachment_image_url( $thumb_id, 'woocommerce_thumbnail' )
					: wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$img_alt  = $thumb_id
					? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true )
					: $term->name;
			?>
			<li class="product-category product ainbaecfwoo-col-item">
				<a href="<?php echo esc_url( is_wp_error( $link ) ? '#' : $link ); ?>"
				   class="ainbaecfwoo-col-card woocommerce-loop-category__link">
					<img src="<?php echo esc_url( $img_src ); ?>"
					     alt="<?php echo esc_attr( $img_alt ?: $term->name ); ?>"
					     class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
					     loading="lazy">
					<h2 class="woocommerce-loop-category__title">
						<?php echo esc_html( $term->name ); ?>
						<mark class="count">
							<?php echo esc_html( sprintf(
								/* translators: %d: product count in this collection */
								_n( '(%d)', '(%d)', $term->count, 'ainbae-product-collections-for-woocommerce' ),
								$term->count
							) ); ?>
						</mark>
					</h2>
					<?php if ( $term->description ) : ?>
						<p class="ainbaecfwoo-col-card-desc">
							<?php echo esc_html( wp_trim_words( $term->description, 20 ) ); ?>
						</p>
					<?php endif; ?>
				</a>
			</li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Assets
	// ══════════════════════════════════════════════════════════════════════════

	public function maybe_enqueue_styles(): void {
		global $post;

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ainbaecfwoo_collections' ) ) {
			wp_enqueue_style( 'woocommerce-layout' );
			wp_enqueue_style( 'woocommerce-smallscreen' );
			wp_enqueue_style( 'woocommerce-general' );
			wp_enqueue_style(
				'ainbaecfwoo-collections-page',
				AINBAECFWOO_COL_URL . 'assets/css/collections-page.css',
				array( 'woocommerce-layout' ),
				AINBAECFWOO_COL_VERSION
			);
		}
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Conflict-safe page creation (called from activation hook).
	// ══════════════════════════════════════════════════════════════════════════

	public static function maybe_create_page(): void {
		// 1. Already have a valid saved page?
		$saved_id = (int) get_option( AINBAECFWOO_COL_PAGE_OPTION, 0 );
		if ( $saved_id && get_post( $saved_id ) instanceof WP_Post ) {
			return;
		}

		// 2. Any existing page already using our shortcode? Adopt it.
		//    Note: searching post_content for shortcode via title search.
		//    We avoid meta_query (slow DB query sniff) by using WP_Query with
		//    a content search that WP performs on the posts table directly.
		$existing = new WP_Query( array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			// Using WP_Query search for shortcode in post_content.
			'_ainbaecfwoo_shortcode_search' => 'ainbaecfwoo_collections',
		) );

		// Fallback: simple direct DB check for shortcode in content.
		if ( ! $existing->have_posts() ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$found_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_type = 'page'
					   AND post_status IN ('publish','draft','private')
					   AND post_content LIKE %s
					 LIMIT 1",
					'%ainbaecfwoo_collections%'
				)
			);
			if ( $found_id ) {
				update_option( AINBAECFWOO_COL_PAGE_OPTION, $found_id );
				return;
			}
		} else {
			update_option( AINBAECFWOO_COL_PAGE_OPTION, $existing->posts[0] );
			return;
		}

		// 3. Page with /collections/ slug exists? Adopt it without touching content.
		$slug_page = get_page_by_path( 'collections', OBJECT, 'page' );
		if ( $slug_page instanceof WP_Post ) {
			update_option( AINBAECFWOO_COL_PAGE_OPTION, $slug_page->ID );
			return;
		}

		// 4. No conflict — create a fresh Collections page.
		$page_id = wp_insert_post( array(
			'post_title'     => __( 'Collections', 'ainbae-product-collections-for-woocommerce' ),
			'post_name'      => 'collections',
			'post_content'   => '[ainbaecfwoo_collections]',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( AINBAECFWOO_COL_PAGE_OPTION, $page_id );
		}
	}
}
