<?php
/**
 * Uninstall cleanup for Ghost Comment Manager
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

/**
 * Resolve cache paths in uploads/ (fallback if autoload.php is not loaded).
 */
if ( ! function_exists( 'gcmgr_cache_paths' ) ) {
	function gcmgr_cache_paths() : array {
		$u    = wp_upload_dir();
		$base = trailingslashit( $u['basedir'] ) . 'ghost-comment-manager/';
		$file = $base . 'classmap.json';
		return array( $base, $file );
	}
}

/**
 * Per-blog cleanup.
 */
function gcmgr_uninstall_for_blog() : void {
	global $wpdb;

	// Options (new + legacy)
	delete_option( 'gcmgr_settings' );
	delete_option( 'gcmgr_metrics' );
	delete_option( 'gcm_settings' );
	delete_option( 'gcm_metrics' );

	// User meta (new + legacy)
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)",
			'_gcmgr_trusted',
			'_gcm_trusted'
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)",
			'_gcmgr_approved_count',
			'_gcm_approved_count'
		)
	);

	// Comment meta (new + legacy)
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN (%s,%s)",
			'_gcmgr_ghost',
			'_gcm_ghost'
		)
	);

	// Transients (new + legacy)
	$like_patterns = array(
		'_transient_gcmgr_%',
		'_transient_timeout_gcmgr_%',
		'_transient_gcm_%',
		'_transient_timeout_gcm_%',
	);
	foreach ( $like_patterns as $like ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
	}

	// Remove cache in uploads/ using WP APIs
	if ( ! function_exists( 'wp_delete_file' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	list( $dir, $file ) = gcmgr_cache_paths();

	// Delete the classmap file (WP-safe)
	if ( ! empty( $file ) && file_exists( $file ) ) {
		wp_delete_file( $file );
	}

	// Recursively delete the cache directory via WP_Filesystem
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();
	global $wp_filesystem;

	if ( $wp_filesystem && ! empty( $dir ) && $wp_filesystem->is_dir( $dir ) ) {
		// true = recursive, 'd' = directory
		$wp_filesystem->delete( trailingslashit( $dir ), true, 'd' );
	}
}

// Multisite-aware uninstall.
if ( is_multisite() && function_exists( 'get_sites' ) && is_network_admin() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $sites as $blog_id ) {
		switch_to_blog( (int) $blog_id );
		gcmgr_uninstall_for_blog();
		restore_current_blog();
	}
} else {
	gcmgr_uninstall_for_blog();
}
