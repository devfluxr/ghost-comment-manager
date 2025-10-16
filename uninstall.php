<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

/**
 * Per-blog cleanup so we can reuse it for multisite.
 */
function gcmgr_uninstall_for_blog() : void {
    global $wpdb;

    // Options (new + legacy)
    foreach ( [ 'gcmgr_settings', 'gcmgr_metrics', 'gcm_settings', 'gcm_metrics' ] as $opt ) {
        delete_option( $opt );
    }

    // User meta (new + legacy)
    $usermeta_keys = [
        '_gcmgr_trusted', '_gcmgr_approved_count',
        '_gcm_trusted',   '_gcm_approved_count',
    ];
    $placeholders = implode( ',', array_fill( 0, count( $usermeta_keys ), '%s' ) );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ($placeholders)",
            ...$usermeta_keys
        )
    );

    // Comment meta (new + legacy)
    $commentmeta_keys = [ '_gcmgr_ghost', '_gcm_ghost' ];
    $placeholders = implode( ',', array_fill( 0, count( $commentmeta_keys ), '%s' ) );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN ($placeholders)",
            ...$commentmeta_keys
        )
    );

    // Transients — remove both prefixes, timeout + value
    $like_patterns = [
        '_transient_gcmgr_%',
        '_transient_timeout_gcmgr_%',
        '_transient_gcm_%',
        '_transient_timeout_gcm_%',
    ];
    foreach ( $like_patterns as $like ) {
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ) );
    }

    // Remove cache files
    $cache_dir = trailingslashit( dirname( __FILE__ ) ) . 'cache';
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();
    global $wp_filesystem;
    if ( $wp_filesystem && $wp_filesystem->is_dir( $cache_dir ) ) {
        $files = $wp_filesystem->dirlist( $cache_dir );
        if ( is_array( $files ) ) {
            foreach ( array_keys( $files ) as $fname ) {
                $wp_filesystem->delete( trailingslashit( $cache_dir ) . $fname );
            }
        }
        $wp_filesystem->rmdir( $cache_dir );
    }
}

if ( is_multisite() && function_exists( 'get_sites' ) && is_network_admin() ) {
    $sites = get_sites( [ 'fields' => 'ids' ] );
    foreach ( $sites as $blog_id ) {
        switch_to_blog( (int) $blog_id );
        gcmgr_uninstall_for_blog();
        restore_current_blog();
    }
} else {
    gcmgr_uninstall_for_blog();
}
