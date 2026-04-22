<?php
/**
 * Plugin settings page — WooCommerce → Ainbae Collections.
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Settings {

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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Menu
	// ══════════════════════════════════════════════════════════════════════════

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Ainbae Collections Settings', 'ainbae-product-collections-for-woocommerce' ),
			__( 'Ainbae Collections', 'ainbae-product-collections-for-woocommerce' ),
			'manage_woocommerce',
			'ainbae-collections-settings',
			array( $this, 'render_page' )
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Save
	// ══════════════════════════════════════════════════════════════════════════

	public function save_settings(): void {
		if (
			! isset( $_POST['ainbae_col_settings_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['ainbae_col_settings_nonce'] ) ),
				'ainbae_col_save_settings_action'
			) ||
			! current_user_can( 'manage_woocommerce' ) ||
			! isset( $_POST['ainbae_col_save'] )
		) {
			return;
		}

		$page_id = isset( $_POST[ AINBAE_COL_PAGE_OPTION ] )
			? absint( wp_unslash( $_POST[ AINBAE_COL_PAGE_OPTION ] ) )
			: 0;
		update_option( AINBAE_COL_PAGE_OPTION, $page_id );

		wp_safe_redirect( add_query_arg( array(
			'page'   => 'ainbae-collections-settings',
			'saved'  => '1',
			'_nonce' => wp_create_nonce( 'ainbae_col_updated' ),
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Render
	// ══════════════════════════════════════════════════════════════════════════

	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Access denied.', 'ainbae-product-collections-for-woocommerce' ) );
		}

		$saved_page_id   = (int) get_option( AINBAE_COL_PAGE_OPTION, 0 );
		$option_field    = esc_attr( AINBAE_COL_PAGE_OPTION );   // Pre-escaped for output.

		$saved = '';
		if (
			isset( $_GET['saved'], $_GET['_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ), 'ainbae_col_updated' )
		) {
			$saved = '1';
		}
		?>
		<div class="wrap" id="ainbae-col-settings-wrap">
			<h1><?php esc_html_e( 'Ainbae Collections Settings', 'ainbae-product-collections-for-woocommerce' ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e( '✓ Settings saved.', 'ainbae-product-collections-for-woocommerce' ); ?></strong></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'ainbae_col_save_settings_action', 'ainbae_col_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>

						<tr>
							<th scope="row">
								<label for="<?php echo $option_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above ?>">
									<?php esc_html_e( 'Collections Page', 'ainbae-product-collections-for-woocommerce' ); ?>
								</label>
							</th>
							<td>
								<?php
								wp_dropdown_pages( array(
									'name'              => $option_field, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages escapes internally
									'id'                => $option_field, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above
									'selected'          => absint( $saved_page_id ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages escapes internally
									'show_option_none'  => esc_html__( '— Select a page —', 'ainbae-product-collections-for-woocommerce' ),
									'option_none_value' => '0',
								) );
								?>
								<p class="description">
									<?php esc_html_e( 'The page that lists all your collections. Add the [ainbae_collections] shortcode to it.', 'ainbae-product-collections-for-woocommerce' ); ?>
									<?php if ( $saved_page_id && get_post( $saved_page_id ) ) : ?>
										&nbsp;
										<a href="<?php echo esc_url( get_permalink( $saved_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'View page ↗', 'ainbae-product-collections-for-woocommerce' ); ?>
										</a>
										&nbsp;|&nbsp;
										<a href="<?php echo esc_url( (string) get_edit_post_link( $saved_page_id ) ); ?>">
											<?php esc_html_e( 'Edit page', 'ainbae-product-collections-for-woocommerce' ); ?>
										</a>
									<?php endif; ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Shortcode', 'ainbae-product-collections-for-woocommerce' ); ?></th>
							<td>
								<code>[ainbae_collections]</code>
								<p class="description">
									<?php esc_html_e( 'Paste into any page or widget to display your collections grid. Optional attributes:', 'ainbae-product-collections-for-woocommerce' ); ?>
									<br>
									<code>[ainbae_collections columns="3" orderby="name" order="ASC" hide_empty="0" limit="-1"]</code>
								</p>
							</td>
						</tr>

					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="ainbae_col_save" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'ainbae-product-collections-for-woocommerce' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
