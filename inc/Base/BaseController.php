<?php
namespace Devfluxr\Gcmgr\Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class BaseController {

    protected string $plugin_path;
    protected string $plugin_url;
    protected string $plugin;

    public function __construct() {
        // Main file path (works even if moved)
        $this->plugin      = basename( dirname( __DIR__, 2 ) ) . '/ghost-comment-manager.php';
        $this->plugin_path = defined('GCMGR_PLUGIN_DIR') ? GCMGR_PLUGIN_DIR : plugin_dir_path( dirname( __DIR__ ) );
        $this->plugin_url  = defined('GCMGR_PLUGIN_URL') ? GCMGR_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) );
    }
}
