<?php
namespace Devfluxr\Gcmgr\Pages;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Base\BaseController;
use Devfluxr\Gcmgr\Analytics\Metrics;

class Admin extends BaseController
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_admin_pages']);
    }

    public function add_admin_pages(): void
    {
        // Top-level menu
        add_menu_page(
            __('Ghost Comment Manager', 'ghost-comment-manager'), // page_title
            __('Ghost Comments', 'ghost-comment-manager'),        // menu_title
            'moderate_comments',
            'gcmgr_plugin',
            [$this, 'render_admin'],
            'dashicons-testimonial',
            65
        );

        // 1) First submenu = Dashboard
        add_submenu_page(
            'gcmgr_plugin',
            __('Dashboard', 'ghost-comment-manager'),
            __('Dashboard', 'ghost-comment-manager'),
            'moderate_comments',
            'gcmgr_plugin',
            [$this, 'render_admin']
        );

        // 2) Pro Features promo submenu — only if PRO is NOT active
        if (!class_exists('\GcmPro\Init') && !defined('GCMGR_PRO_VERSION')) {
            add_submenu_page(
                'gcmgr_plugin',
                __('Pro Features', 'ghost-comment-manager'),
                __('Pro Features', 'ghost-comment-manager'),
                'manage_options',
                'gcmgr_pro_features',
                [$this, 'render_pro_features']
            );
        }
    }

    public function render_admin(): void
    {
        // live counts
        $metrics = Metrics::get_all();

        // trusted users count
        $trusted_users = new \WP_User_Query([
            'meta_key' => \Devfluxr\Gcmgr\Comments\TrustService::META_TRUSTED,
            'meta_value' => 1,
            'fields' => 'ID',
            'number' => 1,
        ]);
        $trusted_total = (int) $trusted_users->get_total();

        // ghost pending (approved + ghost flag)
        $ghost_pending = get_comments([
            'status' => 'approve',
            'type' => 'comment',
            'meta_key' => \Devfluxr\Gcmgr\Comments\GhostPublisher::META_GHOST,
            'meta_value' => 1,
            'count' => true,
        ]);

        $data = [
            'trusted_total' => $trusted_total,
            'ghost_pending' => (int) $ghost_pending,
            'ghost_marked' => (int) $metrics['ghost_marked'],
            'ghost_confirmed' => (int) $metrics['ghost_confirmed'],
            'auto_trusted' => (int) $metrics['auto_trusted'],
            'blocked' => [
                'honeypot' => (int) $metrics['blocked_honeypot'],
                'min_submit' => (int) $metrics['blocked_min_submit'],
                'rate_min' => (int) $metrics['blocked_rate_min'],
                'rate_hour' => (int) $metrics['blocked_rate_hour'],
                'links' => (int) $metrics['blocked_links'],
                'blocklist' => (int) $metrics['blocked_blocklist'],
                'auto_close' => (int) $metrics['blocked_auto_close'],
                'len_min' => (int) $metrics['blocked_len_min'],
                'len_max' => (int) $metrics['blocked_len_max'],
                'duplicate' => (int) $metrics['blocked_duplicate'],
            ],
        ];

        $path = $this->plugin_path . 'templates/admin.php';
        $data_var = $data;
        require $path;
    }

    public function render_pro_features(): void
    {
        require $this->plugin_path . 'templates/pro-features.php';
    }
}
