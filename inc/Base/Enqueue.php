<?php
namespace Devfluxr\Gcmgr\Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Enqueue
{
    public function register() : void
    {
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
        add_action( 'wp_enqueue_scripts',    [ $this, 'frontend_assets' ] );
    }

    /**
     * Load assets only on our admin pages.
     * Slugs must match your menu/page slugs.
     */
    private function is_our_page() : bool
    {
        if ( ! is_admin() ) { return false; }
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        return in_array( $page, [ 'gcmgr_plugin', 'gcmgr_settings', 'gcmgr_pro_features' ], true );
    }

    public function admin_assets( $hook_suffix ) : void
    {
        if ( ! $this->is_our_page() ) {
            return;
        }

        $base = trailingslashit( defined('GCMGR_PLUGIN_URL') ? GCMGR_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) );
        $ver  = defined('GCMGR_VERSION') ? GCMGR_VERSION : '1.0.0';

        wp_register_style( 'gcmgr-admin', $base . 'assets/css/admin.css', [], $ver );
        wp_enqueue_style( 'gcmgr-admin' );

        wp_register_script( 'gcmgr-admin', $base . 'assets/js/admin.js', [ 'jquery' ], $ver, true );
        wp_localize_script( 'gcmgr-admin', 'gcmgrAdmin', [
            'ajax'  => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'gcmgr_admin' ),
        ] );
        wp_enqueue_script( 'gcmgr-admin' );
    }

    /** Load frontend CSS on singular content where comments are relevant */
    public function frontend_assets() : void
    {
        if ( is_admin() ) { return; }
        if ( ! is_singular() ) { return; }
        if ( ! ( comments_open() || get_comments_number() ) ) { return; }

        $base = trailingslashit( defined('GCMGR_PLUGIN_URL') ? GCMGR_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) ) );
        $ver  = defined('GCMGR_VERSION') ? GCMGR_VERSION : '1.0.0';

        wp_enqueue_style( 'gcmgr-frontend', $base . 'assets/css/frontend.css', [], $ver );
    }
}
