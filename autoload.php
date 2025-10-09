<?php
if ( ! defined('ABSPATH') ) { exit; }

if ( ! defined('GCMGR_CACHE_DIR') ) {
    define('GCMGR_CACHE_DIR', plugin_dir_path(__FILE__) . 'cache/');
}
if ( ! defined('GCMGR_CLASSMAP_FILE') ) {
    define('GCMGR_CLASSMAP_FILE', GCMGR_CACHE_DIR . 'classmap.json');
}

require_once __DIR__ . '/inc/Support/Autoloader.php';
require_once __DIR__ . '/inc/Support/ClassmapBuilder.php';

$loader = new \Devfluxr\Gcmgr\Support\Autoloader();

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
    if ( function_exists('wp_filesystem') === false ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();
    global $wp_filesystem;
    if ( $wp_filesystem && $wp_filesystem->is_dir( GCMGR_CACHE_DIR ) && $wp_filesystem->is_writable( GCMGR_CACHE_DIR ) ) {
        $wp_filesystem->put_contents( GCMGR_CLASSMAP_FILE, wp_json_encode( $map ), FS_CHMOD_FILE );
    }
}

$loader->setClassMap( $map );
$loader->register();

$helpersDir = __DIR__ . '/inc/Functions';
if ( is_dir( $helpersDir ) ) {
    foreach ( glob( $helpersDir.'/*.php' ) as $helper ) {
        require_once $helper;
    }
}