<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

function gcmgr_uninstall_for_blog() : void {
    global $wpdb;

    // Options (new + legacy)
    delete_option('gcmgr_settings');
    delete_option('gcmgr_metrics');
    delete_option('gcm_settings');
    delete_option('gcm_metrics');

    // User meta
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)", '_gcmgr_trusted', '_gcm_trusted') );
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s)", '_gcmgr_approved_count', '_gcm_approved_count') );

    // Comment meta
    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN (%s,%s)", '_gcmgr_ghost', '_gcm_ghost') );

    // Transients (new + legacy)
    $patterns = [
        '_transient_gcmgr_%', '_transient_timeout_gcmgr_%',
        '_transient_gcm_%',   '_transient_timeout_gcm_%',
    ];
    foreach ( $patterns as $like ) {
        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like) );
    }

    // Remove cache in uploads/
    if ( function_exists( 'gcmgr_cache_paths' ) ) {
        list( $dir, $file ) = gcmgr_cache_paths();
        if ( file_exists( $file ) ) { @unlink( $file ); }
        if ( is_dir( $dir ) ) {
            foreach ( glob( trailingslashit( $dir ) . '*', GLOB_NOSORT ) as $f ) {
                @unlink( $f );
            }
            @rmdir( $dir );
        }
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
