<?php
/**
 * Uninstall — runs when the plugin is deleted from WordPress.
 *
 * Removes:
 *  - The Collections page option (ainbaecfwoo_col_page_id).
 *  - The thumbnail_id term meta stored on every collection term.
 *
 * NOTE: Collection terms and the taxonomy itself are intentionally left in
 * the database. Silently deleting user-created content on uninstall is
 * considered bad practice and can cause data loss.
 *
 * @package AinbaeCFWoo\Collections
 */

// WordPress sets this constant before calling uninstall.php.
// Bail immediately if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ── 1. Remove plugin option ────────────────────────────────────────────────────
delete_option( 'ainbaecfwoo_col_page_id' );

// ── 2. Remove thumbnail term meta from all collection terms ───────────────────
// get_terms() is not available this early, so use $wpdb directly.
global $wpdb;

$taxonomy = 'ainbaecfwoo_collection';

// Fetch all term IDs registered under this taxonomy.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$term_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
		$taxonomy
	)
);

if ( ! empty( $term_ids ) ) {
	foreach ( $term_ids as $term_id ) {
		delete_term_meta( (int) $term_id, 'thumbnail_id' );
	}
}
