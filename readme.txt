=== Ainbae Product Collections for WooCommerce ===
Contributors: ainbae
Tags: woocommerce, collections, taxonomy, products
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
WC requires at least: 6.0
WC tested up to: 10.7
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.ainbae.com/donate

Adds a "Collections" taxonomy to WooCommerce, working exactly like Product Categories.

== Description ==

Ainbae Product Collections adds a Collections taxonomy to WooCommerce products. Collection archive pages automatically inherit your theme's exact product category layout — same sidebar behaviour, same columns, same CSS — on every WooCommerce-compatible theme.

= Features =
* Collections taxonomy (hierarchical, like product categories)
* Collection archive pages that look identical to your Product Category pages on every theme
* Thumbnail image upload on Add New Collection and Edit Collection admin screens
* Collections landing page with [ainbae_collections] shortcode
* One "Collections" column in the Products list (no duplicates)
* One "Collections" menu item under Products (no duplicates)
* Filter products by collection in the admin Products list
* Correct breadcrumbs with Collections page as parent
* WooCommerce HPOS compatible

= Shortcode =
Use [ainbae_collections] on any page to display a grid of all collections.

Optional attributes:
* columns — number of columns (default: 3)
* orderby — sort field: name, count, slug (default: name)
* order — ASC or DESC (default: ASC)
* hide_empty — 0 or 1 (default: 0)
* limit — max number of collections, -1 for all (default: -1)

Example:
[ainbae_collections columns="4" orderby="count" order="DESC"]

== Installation ==

**Automatic installation (recommended)**
1. In your WordPress admin, go to **Plugins → Add New**.
2. Search for *Ainbae Product Collections for WooCommerce*.
3. Click **Install Now**, then **Activate**.
4. Go to **Products → Collections** to create your first collection
5. Assign collections to products from the product edit screen sidebar

**Manual installation**
1. Download the plugin zip file.
2. Go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate the plugin.
4. Go to **Products → Collections** to create your first collection
5. Assign collections to products from the product edit screen sidebar

== Frequently Asked Questions ==

= Does this replace Product Categories? =
No. Collections is a completely separate taxonomy that runs alongside Product Categories.

= Do collection URLs work automatically? =
Yes. On plugin activation rewrite rules are flushed so `/collection/summer/` works immediately.

= Can I nest collections? =
Yes. Full parent/child hierarchy is supported — both in the admin and in the frontend URL structure.

= Is it compatible with WPML / Polylang? =
The taxonomy is registered as translatable-friendly. WPML/Polylang users should register the taxonomy in their plugin settings for string translation.

== Changelog ==

= 1.2.0 =
* FIX: Collection archive pages now inherit exact layout (sidebar, columns, CSS) from Product Category pages on every WooCommerce theme. Done by making is_product_category() return true during template render, which is what themes check for layout decisions.
* FIX: All text domains corrected from 'ainbae-collections' to 'ainbae-product-collections-for-woocommerce' (50+ occurrences across all files).
* FIX: Unescaped output errors in settings page — all values pre-escaped before output.
* FIX: Removed unprefixed hook name (loop_shop_per_page apply_filters call).
* FIX: Removed slow meta_query from page adoption check; replaced with direct DB query.
* FIX: Missing /languages directory added.
* NEW: Thumbnail image upload field on Add New Collection admin screen (Issue #3).
* NEW: term_link filter ensures /collection/ URLs stay correct after layout spoof.

= 1.1.0 =
* FIX: Collections no longer appeared twice in the Products admin menu.
* FIX: Only one "Collections" column in the Products list table.
* FIX: Collection archive pages use same template as Product Category pages.
* NEW: [ainbae_collections] shortcode for collections landing page.
* NEW: Conflict-safe page creation on activation.
* NEW: WooCommerce → Ainbae Collections settings page.

= 1.0.0 =
* Initial release.


== Upgrade Notice ==
 
= 1.2.0 =
* Collection archive pages now inherit exact layout (sidebar, columns, CSS) from Product Category pages on every WooCommerce theme. Done by making is_product_category() return true during template render, which is what themes check for layout decisions.
* All text domains corrected from 'ainbae-collections' to 'ainbae-product-collections-for-woocommerce' (50+ occurrences across all files).
* Unescaped output errors in settings page fixed — all values pre-escaped before output.
* Removed unprefixed hook name (loop_shop_per_page apply_filters call).
* Removed slow meta_query from page adoption check; replaced with direct DB query.
* Missing /languages directory added.
* NEW: Thumbnail image upload field on Add New Collection admin screen (Issue #3).
* NEW: term_link filter ensures /collection/ URLs stay correct after layout spoof.