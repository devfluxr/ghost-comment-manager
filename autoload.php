<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Resolve cache paths in uploads/, not inside the plugin folder.
 */
if ( ! function_exists( 'gcmgr_cache_paths' ) ) {
    function gcmgr_cache_paths() : array {
        $u = wp_upload_dir();
        $base = trailingslashit( $u['basedir'] ) . 'ghost-comment-manager/';
        $file = $base . 'classmap.json';
        return [ $base, $file ];
    }
}

list( $gcmgr_cache_dir, $gcmgr_classmap_file ) = gcmgr_cache_paths();

if ( ! defined('GCMGR_CACHE_DIR') )  define('GCMGR_CACHE_DIR',  $gcmgr_cache_dir);
if ( ! defined('GCMGR_CLASSMAP_FILE') ) define('GCMGR_CLASSMAP_FILE', $gcmgr_classmap_file);

require_once __DIR__ . '/inc/Support/Autoloader.php';
require_once __DIR__ . '/inc/Support/ClassmapBuilder.php';

$loader = new \Devfluxr\Gcmgr\Support\Autoloader();

// PSR-4 map
$psr4 = [
    'Devfluxr\\Gcmgr\\' => plugin_dir_path(__FILE__) . 'inc',
];
$proInc = plugin_dir_path(__FILE__) . 'pro/inc';
if ( is_dir( $proInc ) ) {
    $psr4['GcmPro\\'] = $proInc;
}
foreach ( $psr4 as $prefix => $dir ) {
    $loader->addPsr4( $prefix, $dir );
}

// Load or build classmap
$map = [];
if ( file_exists( GCMGR_CLASSMAP_FILE ) ) {
    $json = file_get_contents( GCMGR_CLASSMAP_FILE );
    $arr  = json_decode( (string) $json, true );
    if ( is_array( $arr ) ) { $map = $arr; }
} else {
    if ( ! is_dir( GCMGR_CACHE_DIR ) ) {
        wp_mkdir_p( GCMGR_CACHE_DIR );
    }
    $map = \Devfluxr\Gcmgr\Support\ClassmapBuilder::build( $psr4 );

    // Write cache only if uploads dir is writable
    if ( wp_is_writable( GCMGR_CACHE_DIR ) ) {
        if ( ! function_exists('WP_Filesystem') ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;
        if ( $wp_filesystem ) {
            $wp_filesystem->put_contents( GCMGR_CLASSMAP_FILE, wp_json_encode( $map ), FS_CHMOD_FILE );
        }
    }
}

$loader->setClassMap( $map );
$loader->register();

// Optional helpers
$helpersDir = __DIR__ . '/inc/Functions';
if ( is_dir( $helpersDir ) ) {
    foreach ( glob( $helpersDir.'/*.php' ) as $helper ) {
        require_once $helper;
    }
}
