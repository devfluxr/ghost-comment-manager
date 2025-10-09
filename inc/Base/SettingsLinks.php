<?php
namespace Devfluxr\Gcmgr\Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class SettingsLinks extends BaseController
{
    public function register(): void
    {
        add_filter("plugin_action_links_{$this->plugin}", [$this, 'settings_link']);
    }
    public function settings_link(array $links): array
    {
        $links[] = '<a href="' . admin_url('admin.php?page=gcmgr_settings') . '">' . esc_html__('Settings', 'ghost-comment-manager') . '</a>';
        if (!class_exists('\GcmPro\Init') && !defined('GCM_PRO_VERSION')) {
            $envato_url = 'https://www.google.com/search?q=ghost+comment+manager+pro+site%3Acodecanyon.net';
            $links[] = '<a href="' . esc_url($envato_url) . '" target="_blank" rel="noopener" style="font-weight:600">'
                . esc_html__('Get PRO', 'ghost-comment-manager') . '</a>';
        }
        return $links;
    }

}
