<?php
/**
 * Plugin Name: Ghost Comment Manager
 * Description: Trusted comments → auto-publish as “ghost” (mod-only flagged) until confirmed.
 * Version: 0.1.3
 * Author: DevFluxr
 * Author URI: https://devfluxr.com/
 * Plugin URI: https://github.com/devfluxr/ghost-comment-manager
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ghost-comment-manager
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.8
 */


if ( ! defined('ABSPATH') ) { exit; }

define('GCMGR_VERSION', '0.1.2');
define('GCMGR_PLUGIN_FILE', __FILE__);
define('GCMGR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GCMGR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

register_activation_hook(__FILE__, function() {
    $old_settings = get_option('gcm_settings', null);
    if ($old_settings !== null) {
        add_option('gcmgr_settings', $old_settings, '', 'no');
        delete_option('gcm_settings');
    }
    $old_metrics = get_option('gcm_metrics', null);
    if ($old_metrics !== null) {
        add_option('gcmgr_metrics', $old_metrics, '', 'no');
        delete_option('gcm_metrics');
    }

    if ( get_option('gcmgr_settings', null ) === null ) {
        if ( class_exists('\\Devfluxr\\Gcmgr\\Settings\\Registry') ) {
            add_option( 'gcmgr_settings', \Devfluxr\Gcmgr\Settings\Registry::defaults(), '', 'no' );
        } else {
            add_option( 'gcmgr_settings', [], '', 'no' );
        }
    }
    if ( get_option('gcmgr_metrics', null ) === null ) {
        add_option( 'gcmgr_metrics', [], '', 'no' );
    }

    if ( ! is_dir( GCMGR_CACHE_DIR ) ) {
        wp_mkdir_p( GCMGR_CACHE_DIR );
    }
    $psr4 = ['Devfluxr\\Gcmgr\\' => plugin_dir_path(__FILE__) . 'inc'];
    $proInc = plugin_dir_path(__FILE__) . 'pro/inc';
    if ( is_dir( $proInc ) ) {
        $psr4['GcmPro\\'] = $proInc;
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
});

add_action('plugins_loaded', function () {
    if ( class_exists('\\Devfluxr\\Gcmgr\\Init') ) {
        \Devfluxr\Gcmgr\Init::register_services();
    }
});