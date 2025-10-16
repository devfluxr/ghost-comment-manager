<?php
namespace Devfluxr\Gcmgr\Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class Deactivate {
    public static function deactivate() : void {
        flush_rewrite_rules();
    }
}
