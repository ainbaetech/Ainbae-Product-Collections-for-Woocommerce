=== Ainbae Product Collections for WooCommerce ===
Contributors: ainbae
Tags: woocommerce, collections, taxonomy, products
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.2
WC requires at least: 6.0
WC tested up to: 11.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Adds a "Collections" taxonomy to WooCommerce, working exactly like Product Categories.

== Description ==
Create curated product collections in WooCommerce without affecting your existing product categories.

Ainbae Product Collections for WooCommerce adds a dedicated Collections taxonomy that works alongside Product Categories. Build seasonal collections, fashion collections, featured collections, brand collections, gift guides, and more.

Collection archive pages automatically inherit your theme's WooCommerce category layout, ensuring a seamless shopping experience without additional configuration.

= Features =
* Collections taxonomy (hierarchical, like product categories)
* Collection archive pages that look identical to your Product Category pages on every theme
* Thumbnail image upload on Add New Collection and Edit Collection admin screens
* Collections landing page with [ainbaecfwoo_collections] shortcode
* One "Collections" column in the Products list (no duplicates)
* One "Collections" menu item under Products (no duplicates)
* Filter products by collection in the admin Products list
* Correct breadcrumbs with Collections page as parent
* WooCommerce HPOS compatible
* Dedicated Collections settings page under WooCommerce
* Automatic Collections page creation with shortcode support
* Product-to-Collection assignment from the product editor
* Collection thumbnails for visually rich collection grids
* Parent/Child collection hierarchy support
* Collection landing page with responsive grid layout
* Collection archive pages with WooCommerce filters and theme integration
* SEO-friendly collection URLs

= Shortcode =
Use [ainbaecfwoo_collections] on any page to display a grid of all collections.

Optional attributes:
* columns — number of columns (default: 3)
* orderby — sort field: name, count, slug (default: name)
* order — ASC or DESC (default: ASC)
* hide_empty — 0 or 1 (default: 0)
* limit — max number of collections, -1 for all (default: -1)

Example:
[ainbaecfwoo_collections columns="4" orderby="count" order="DESC"]

= How It Works =
1. Create collections from Products → Collections.
2. Upload a thumbnail image for each collection.
3. Assign collections to products from the product editor.
4. Create or select a Collections page from WooCommerce → Ainbae Collections.
5. Display all collections using the shortcode: [ainbaecfwoo_collections]
6. Customers can browse collection landing pages and individual collection archives exactly like WooCommerce product categories.

= Perfect For =
* Fashion stores
* Seasonal campaigns
* Holiday promotions
* Product launches
* Featured product groups
* Beauty collections
* Summer and Winter collections
* Brand showcases
* Gift guides
* Curated product selections

== Screenshots ==

1. Collections management screen with hierarchy support and thumbnail uploads.
2. Ainbae Collections settings page for selecting the collections landing page.
3. Collections page created using the [ainbaecfwoo_collections] shortcode.
4. Product editor sidebar showing collection assignment options.
5. Frontend collections landing page displaying collection thumbnails and product counts.
6. Individual collection archive page displaying products using the active theme's WooCommerce layout.

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

= Can products belong to multiple collections? =
Yes. Products can be assigned to multiple collections just like WooCommerce product categories.

= Can I add images to collections? =
Yes. Each collection supports a thumbnail image that is displayed in collection grids and landing pages.

= Will collections affect my existing categories? =
No. Collections are completely separate from WooCommerce product categories.

= Do collection pages match my theme design? =
Yes. Collection archives automatically inherit your theme's WooCommerce category layout, styling, sidebars, filters, and product grids.

= Does this replace Product Categories? =
No. Collections is a completely separate taxonomy that runs alongside Product Categories.

= Do collection URLs work automatically? =
Yes. On plugin activation rewrite rules are flushed so `/collection/summer/` works immediately.

= Can I nest collections? =
Yes. Full parent/child hierarchy is supported — both in the admin and in the frontend URL structure.

= Is it compatible with WPML / Polylang? =
The taxonomy is registered as translatable-friendly. WPML/Polylang users should register the taxonomy in their plugin settings for string translation.

== Changelog ==

= 1.2.2 =
* Update: Tested up to WooCommerce 11.1.0.
* Update: Tested up to WordPress 7.1.

= 1.2.1 =
* FIX: Shortcode collection cards now inherit full WooCommerce/theme grid layout, matching product category card sizing and alignment exactly.
* FIX: Removed sort arrows from the Collections column in the Products list table — now matches the native Categories column behaviour.
* Updated readme with expanded features, How It Works guide, Screenshots, Perfect For section, and additional FAQs.

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
* NEW: [ainbaecfwoo_collections] shortcode for collections landing page.
* NEW: Conflict-safe page creation on activation.
* NEW: WooCommerce → Ainbae Collections settings page.

= 1.0.0 =
* Initial release.


== Upgrade Notice ==
 
= 1.2.1 =
- Updated tested up to WooCommerce 11.1.0 and WordPress 7.1.