<?php
/**
 * Plugin Name: Ghost Comment Manager
 * Description: Trusted comments → auto-publish as “ghost” (mod-only flagged) until confirmed.
 * Version: 0.1.5
 * Author: devfluxr
 * Plugin URI: https://profiles.wordpress.org/devfluxr/
 * Author URI: https://profiles.wordpress.org/devfluxr/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ghost-comment-manager
 * Tags: comments, moderation, spam, trust, ghost
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
    // Legacy → New (safe copy only; do NOT delete legacy keys to avoid conflicts with other plugins).
    $legacy_settings = get_option('gcm_settings', null);
    if ( is_array($legacy_settings) && isset($legacy_settings['shield_honeypot']) ) {
        if ( get_option('gcmgr_settings', null ) === null ) {
            add_option('gcmgr_settings', $legacy_settings, '', 'no');
        }
        // Marker that we migrated from *our* legacy plugin (optional if you want uninstall to clean legacy too)
        add_option('gcmgr_migrated_legacy', 1, '', 'no');
    }

    $legacy_metrics = get_option('gcm_metrics', null);
    if ( is_array($legacy_metrics) && array_key_exists('ghost_marked', (array)$legacy_metrics) ) {
        if ( get_option('gcmgr_metrics', null ) === null ) {
            add_option('gcmgr_metrics', $legacy_metrics, '', 'no');
        }
        add_option('gcmgr_migrated_legacy', 1, '', 'no');
    }

    // Ensure defaults exist
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

    // build classmap cache
    if ( ! defined('GCMGR_CACHE_DIR') ) {
        define('GCMGR_CACHE_DIR', plugin_dir_path(__FILE__) . 'cache/');
    }
    if ( ! defined('GCMGR_CLASSMAP_FILE') ) {
        define('GCMGR_CLASSMAP_FILE', GCMGR_CACHE_DIR . 'classmap.json');
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
