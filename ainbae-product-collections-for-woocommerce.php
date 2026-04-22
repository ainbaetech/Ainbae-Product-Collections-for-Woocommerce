<?php

/**
 * Plugin Name:          Ainbae Product Collections for WooCommerce
 * Description:          Adds a "Collections" taxonomy to WooCommerce — works exactly like Product Categories with full hierarchy, admin panel, and frontend archive support.
 * Version:              1.2.0
 * Requires at least:    5.8
 * Requires PHP:         7.4
 * Author:               Ainbae
 * Author URI:           https://ainbae.com
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          ainbae-product-collections-for-woocommerce
 * Domain Path:          /languages
 * WC requires at least: 6.0
 * WC tested up to:      10.7
 *
 * @package Ainbae\Collections
 */

defined('ABSPATH') || exit;

// ── Constants ──────────────────────────────────────────────────────────────────
define('AINBAE_COL_VERSION',     '1.2.0');
define('AINBAE_COL_TAXONOMY',    'product_collection');
define('AINBAE_COL_SLUG',        'collection');
define('AINBAE_COL_PAGE_OPTION', 'ainbae_col_page_id');
define('AINBAE_COL_FILE',        __FILE__);
define('AINBAE_COL_PATH',        plugin_dir_path(__FILE__));
define('AINBAE_COL_URL',         plugin_dir_url(__FILE__));
define('AINBAE_COL_TD',          'ainbae-product-collections-for-woocommerce');

// ── Includes ───────────────────────────────────────────────────────────────────
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-taxonomy.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-admin.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-frontend.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-page.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-settings.php';

/**
 * Main bootstrap class — singleton.
 */
final class Ainbae_Product_Collections
{

	/** @var self|null */
	private static ?self $instance = null;

	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		register_activation_hook(AINBAE_COL_FILE,   array($this, 'on_activate'));
		register_deactivation_hook(AINBAE_COL_FILE, array($this, 'on_deactivate'));
		add_action('plugins_loaded', array($this, 'boot'), 10);
	}

	public function boot(): void
	{
		if (! class_exists('WooCommerce')) {
			add_action('admin_notices', array($this, 'notice_wc_missing'));
			return;
		}

		add_action('init', array(Ainbae_Collections_Taxonomy::instance(), 'register'), 5);

		Ainbae_Collections_Admin::instance()->init();
		Ainbae_Collections_Frontend::instance()->init();
		Ainbae_Collections_Page::instance()->init();
		Ainbae_Collections_Settings::instance()->init();
	}

	public function on_activate(): void
	{
		Ainbae_Collections_Taxonomy::instance()->register();
		flush_rewrite_rules();
		Ainbae_Collections_Page::maybe_create_page();
	}

	public function on_deactivate(): void
	{
		flush_rewrite_rules();
	}

	public function notice_wc_missing(): void
	{
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__('Ainbae Product Collections requires WooCommerce to be installed and active.', 'ainbae-product-collections-for-woocommerce')
		);
	}
}

Ainbae_Product_Collections::get_instance();
