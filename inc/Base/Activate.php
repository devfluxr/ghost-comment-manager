<?php
namespace Devfluxr\Gcmgr\Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class Activate {
    public static function activate() : void {
        // Future: add options/migrations.
        flush_rewrite_rules();
    }
}
