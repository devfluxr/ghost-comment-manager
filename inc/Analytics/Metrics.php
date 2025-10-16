<?php
namespace Devfluxr\Gcmgr\Analytics;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class Metrics {
    private const OPTION = 'gcmgr_metrics';

    public static function bump( string $key, int $by = 1 ) : void {
        $all = get_option( self::OPTION, [] );
        $all[ $key ] = isset($all[$key]) ? (int)$all[$key] + $by : $by;
        update_option( self::OPTION, $all, false );
    }

    public static function get_all() : array {
        $all = get_option( self::OPTION, [] );
        $defaults = [
            // blocks
            'blocked_honeypot'    => 0,
            'blocked_min_submit'  => 0,
            'blocked_rate_min'    => 0,
            'blocked_rate_hour'   => 0,
            'blocked_links'       => 0,
            'blocked_blocklist'   => 0,
            'blocked_auto_close'  => 0,
            'blocked_len_min'     => 0,
            'blocked_len_max'     => 0,
            'blocked_duplicate'   => 0,
            // ghost/trust flow
            'ghost_marked'        => 0,
            'ghost_confirmed'     => 0,
            'auto_trusted'        => 0,
        ];
        return wp_parse_args( $all, $defaults );
    }
}
