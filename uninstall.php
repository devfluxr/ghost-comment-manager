<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

function gcmgr_uninstall_for_blog() : void {
    global $wpdb;

    delete_option('gcmgr_settings');
    delete_option('gcmgr_metrics');
    // legacy
    delete_option('gcm_settings');
    delete_option('gcm_metrics');

    // user meta
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)", '_gcmgr_trusted', '_gcm_trusted') );
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)", '_gcmgr_approved_count', '_gcm_approved_count') );

    // comment meta
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN (%s,%s)", '_gcmgr_ghost', '_gcm_ghost') );

    // transients (both prefixes)
    $patterns = [
        '_transient_gcmgr_rate_m_%',
        '_transient_timeout_gcmgr_rate_m_%',
        '_transient_gcmgr_rate_h_%',
        '_transient_timeout_gcmgr_rate_h_%',
        '_transient_gcmgr_last_%_hash',
        '_transient_timeout_gcmgr_last_%_hash',
        '_transient_gcm_rate_m_%',
        '_transient_timeout_gcm_rate_m_%',
        '_transient_gcm_rate_h_%',
        '_transient_timeout_gcm_rate_h_%',
        '_transient_gcm_last_%_hash',
        '_transient_timeout_gcm_last_%_hash',
    ];
    foreach ( $patterns as $like ) {
        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like) );
    }

    // remove cache files
    $cache_dir = trailingslashit( dirname(__FILE__) ) . 'cache';
    if ( function_exists('wp_filesystem') === false ) {
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