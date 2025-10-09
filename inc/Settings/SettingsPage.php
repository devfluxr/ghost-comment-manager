<?php
namespace Devfluxr\Gcmgr\Settings;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class SettingsPage {
    public function register() : void {
        add_action('admin_init', [ $this, 'settings' ]);
        add_action('admin_menu', [ $this, 'submenu' ]);
    }

    public function submenu() : void {
        add_submenu_page(
            'gcmgr_plugin',
            __('Settings','ghost-comment-manager'),
            __('Settings','ghost-comment-manager'),
            'manage_options',
            'gcmgr_settings',
            [ $this, 'render' ]
        );
    }

    public function settings() : void {
        register_setting( Registry::OPTION_GROUP, Registry::OPTION_NAME, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize' ],
            'default'           => Registry::defaults(),
        ]);

        // ---- General
        add_settings_section( 'gcmgr_main', __('General','ghost-comment-manager'), '__return_false', 'gcmgr_settings' );
        add_settings_field('auto_trust_threshold', __('Auto-trust after X approvals','ghost-comment-manager'), [ $this, 'field_threshold' ], 'gcmgr_settings','gcmgr_main');
        add_settings_field('role_exclusions', __('Do not ghost these roles','ghost-comment-manager'), [ $this, 'field_roles' ], 'gcmgr_settings','gcmgr_main');
        add_settings_field('indicator', __('Ghost indicator (mods only)','ghost-comment-manager'), [ $this, 'field_indicator' ], 'gcmgr_settings','gcmgr_main');

        // ---- Shield Lite
        add_settings_section( 'gcmgr_shield', __('Shield Lite (Spam/Abuse Guard)','ghost-comment-manager'), '__return_false', 'gcmgr_settings' );
        add_settings_field('shield_honeypot', __('Honeypot field','ghost-comment-manager'), [ $this, 'field_honeypot' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_min_submit_secs', __('Minimum submit time (seconds)','ghost-comment-manager'), [ $this, 'field_min_submit' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_rate', __('Rate limit (per minute / hour)','ghost-comment-manager'), [ $this, 'field_rate' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_link_limit', __('Max links per comment','ghost-comment-manager'), [ $this, 'field_link_limit' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_blocklist', __('Keyword/regex blocklist','ghost-comment-manager'), [ $this, 'field_blocklist' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_auto_close_days', __('Auto-close comments on old posts (days)','ghost-comment-manager'), [ $this, 'field_auto_close' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_length', __('Min/Max length','ghost-comment-manager'), [ $this, 'field_length' ], 'gcmgr_settings','gcmgr_shield');
        add_settings_field('shield_dedupe_seconds', __('Duplicate protection window (seconds)','ghost-comment-manager'), [ $this, 'field_dedupe' ], 'gcmgr_settings','gcmgr_shield');

        // 0.A — Allow extensions (e.g., PRO) to register their own settings sections/fields.
        do_action( 'gcmgr_register_extra_settings' );
    }

    public function sanitize( $input ) : array {
        $o = Registry::get();

        $o['auto_trust_threshold'] = isset($input['auto_trust_threshold']) ? max(0,(int)$input['auto_trust_threshold']) : $o['auto_trust_threshold'];

        $roles = wp_roles()->roles;
        $o['role_exclusions'] = [];
        if ( ! empty($input['role_exclusions']) && is_array($input['role_exclusions']) ) {
            foreach ( $input['role_exclusions'] as $role ) {
                if ( isset($roles[$role]) ) { $o['role_exclusions'][] = $role; }
            }
        }

        $o['indicator_color'] = isset($input['indicator_color']) ? ( sanitize_hex_color($input['indicator_color']) ?: $o['indicator_color'] ) : $o['indicator_color'];
        if ( isset($input['indicator_icon']) ) {
            $icon = wp_strip_all_tags( (string) $input['indicator_icon'] );
            $o['indicator_icon'] = mb_substr($icon, 0, 8);
        }

        // Shield
        $o['shield_honeypot']        = empty($input['shield_honeypot']) ? 0 : 1;
        $o['shield_min_submit_secs'] = isset($input['shield_min_submit_secs']) ? max(0,(int)$input['shield_min_submit_secs']) : $o['shield_min_submit_secs'];
        $o['shield_rate_per_min']    = isset($input['shield_rate_per_min']) ? max(0,(int)$input['shield_rate_per_min']) : $o['shield_rate_per_min'];
        $o['shield_rate_per_hour']   = isset($input['shield_rate_per_hour']) ? max(0,(int)$input['shield_rate_per_hour']) : $o['shield_rate_per_hour'];
        $o['shield_link_limit']      = isset($input['shield_link_limit']) ? max(0,(int)$input['shield_link_limit']) : $o['shield_link_limit'];
        $o['shield_blocklist']       = isset($input['shield_blocklist']) ? (string) $input['shield_blocklist'] : $o['shield_blocklist'];
        $o['shield_auto_close_days'] = isset($input['shield_auto_close_days']) ? max(0,(int)$input['shield_auto_close_days']) : $o['shield_auto_close_days'];
        $o['shield_min_length']      = isset($input['shield_min_length']) ? max(0,(int)$input['shield_min_length']) : $o['shield_min_length'];
        $o['shield_max_length']      = isset($input['shield_max_length']) ? max(0,(int)$input['shield_max_length']) : $o['shield_max_length'];
        $o['shield_dedupe_seconds']  = isset($input['shield_dedupe_seconds']) ? max(0,(int)$input['shield_dedupe_seconds']) : $o['shield_dedupe_seconds'];

        return $o;
    }

    // ---- Fields
    public function field_threshold() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[auto_trust_threshold]" value="%2$d" class="small-text" />',
            esc_attr( Registry::OPTION_NAME ), (int)$o['auto_trust_threshold']);
        echo '<p class="description">'.esc_html__('After this many approved comments, the user becomes Trusted automatically (0 = disable).','ghost-comment-manager').'</p>';
    }

    public function field_roles() : void {
        $o = Registry::get(); $all = wp_roles()->roles;
        foreach ( $all as $key => $r ) {
            printf('<label><input type="checkbox" name="%1$s[role_exclusions][]" value="%2$s" %3$s /> %4$s</label><br/>',
                esc_attr( Registry::OPTION_NAME ), esc_attr($key),
                checked( in_array($key,(array)$o['role_exclusions'],true), true, false ),
                esc_html( translate_user_role($r['name']) ));
        }
        echo '<p class="description">'.esc_html__('Trusted users with these roles will NOT be ghosted (their comments publish normally).','ghost-comment-manager').'</p>';
    }

    public function field_indicator() : void {
        $o = Registry::get();
        printf('<input type="text" name="%1$s[indicator_icon]" value="%2$s" class="regular-text" style="width:80px" /> ',
            esc_attr(Registry::OPTION_NAME), esc_attr($o['indicator_icon']));
        printf('<input type="text" name="%1$s[indicator_color]" value="%2$s" class="regular-text" style="width:120px" />',
            esc_attr(Registry::OPTION_NAME), esc_attr($o['indicator_color']));
        echo '<p class="description">'.esc_html__('Icon (e.g., 👻) and background color (hex) for mod-only ghost marker.','ghost-comment-manager').'</p>';
    }

    // ---- Shield fields
    public function field_honeypot() : void {
        $o = Registry::get();
        printf('<label><input type="checkbox" name="%1$s[shield_honeypot]" value="1" %2$s /> %3$s</label>',
            esc_attr(Registry::OPTION_NAME), checked(1,(int)$o['shield_honeypot'], false),
            esc_html__('Add a hidden honeypot field to deter bots.','ghost-comment-manager'));
    }

    public function field_min_submit() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_min_submit_secs]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_min_submit_secs']);
        echo '<p class="description">'.esc_html__('Minimum seconds between form load and submit. 0 = off.','ghost-comment-manager').'</p>';
    }

    public function field_rate() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_rate_per_min]" value="%2$d" class="small-text" /> / ',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_rate_per_min']);
        printf('<input type="number" min="0" step="1" name="%1$s[shield_rate_per_hour]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_rate_per_hour']);
        echo '<p class="description">'.esc_html__('Max comments allowed per minute / per hour per IP (0 = unlimited).','ghost-comment-manager').'</p>';
    }

    public function field_link_limit() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_link_limit]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_link_limit']);
        echo '<p class="description">'.esc_html__('Auto-block if a comment contains more than this number of links. 0 = unlimited.','ghost-comment-manager').'</p>';
    }

    public function field_blocklist() : void {
        $o = Registry::get();
        printf('<textarea name="%1$s[shield_blocklist]" rows="6" cols="60" class="large-text code">%2$s</textarea>',
            esc_attr(Registry::OPTION_NAME), esc_textarea($o['shield_blocklist']));
        echo '<p class="description">'.esc_html__('One entry per line. Supports plain words or delimiters for regex (e.g., /viagra/i).','ghost-comment-manager').'</p>';
    }

    public function field_auto_close() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_auto_close_days]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_auto_close_days']);
        echo '<p class="description">'.esc_html__('If post is older than X days, block new comments. 0 = off.','ghost-comment-manager').'</p>';
    }

    public function field_length() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_min_length]" value="%2$d" class="small-text" /> / ',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_min_length']);
        printf('<input type="number" min="0" step="1" name="%1$s[shield_max_length]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_max_length']);
        echo '<p class="description">'.esc_html__('Minimum / Maximum characters (0 = unlimited max).','ghost-comment-manager').'</p>';
    }

    public function field_dedupe() : void {
        $o = Registry::get();
        printf('<input type="number" min="0" step="1" name="%1$s[shield_dedupe_seconds]" value="%2$d" class="small-text" />',
            esc_attr(Registry::OPTION_NAME), (int)$o['shield_dedupe_seconds']);
        echo '<p class="description">'.esc_html__('Block duplicate comment text from same IP within N seconds. 0 = off.','ghost-comment-manager').'</p>';
    }

    public function render() : void {
        echo '<div class="wrap"><h1>'.esc_html__('Ghost Comment Manager – Settings','ghost-comment-manager').'</h1>';
        settings_errors();
        echo '<form method="post" action="options.php">';
        settings_fields( Registry::OPTION_GROUP );
        do_settings_sections( 'gcmgr_settings' );
        submit_button();
        echo '</form></div>';
    }
}
