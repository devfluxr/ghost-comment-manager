<?php
namespace Devfluxr\Gcmgr\Settings;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class Registry {
    public const OPTION_GROUP = 'gcmgr_settings_group';
    public const OPTION_NAME  = 'gcmgr_settings';

    public static function defaults() : array {
        return [
            // Core
            'auto_trust_threshold' => 3,
            'role_exclusions'      => [],
            'indicator_color'      => '#eeeeee',
            'indicator_icon'       => '👻',

            // Shield Lite
            'shield_honeypot'          => 1,
            'shield_min_submit_secs'   => 3,
            'shield_rate_per_min'      => 6,
            'shield_rate_per_hour'     => 60,
            'shield_link_limit'        => 3,
            'shield_blocklist'         => "",   // newline-separated words/regex
            'shield_auto_close_days'   => 0,    // 0 = off
            'shield_min_length'        => 3,
            'shield_max_length'        => 5000, // 0 = unlimited
            'shield_dedupe_seconds'    => 120,  // same comment within N seconds
        ];
    }

    public static function get() : array {
        $opts = get_option( self::OPTION_NAME, [] );
        return wp_parse_args( is_array($opts) ? $opts : [], self::defaults() );
    }
}
