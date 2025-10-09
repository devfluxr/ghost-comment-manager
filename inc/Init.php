<?php
namespace Devfluxr\Gcmgr;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Base\Enqueue;
use Devfluxr\Gcmgr\Base\SettingsLinks;
use Devfluxr\Gcmgr\Pages\Admin;
use Devfluxr\Gcmgr\Admin\Notice;
use Devfluxr\Gcmgr\Admin\ListTableColumns;
use Devfluxr\Gcmgr\Comments\RowActions;
use Devfluxr\Gcmgr\Comments\AutoTrustManager;
use Devfluxr\Gcmgr\Comments\GhostPublisher;
use Devfluxr\Gcmgr\Comments\FiltersUi;
use Devfluxr\Gcmgr\Comments\BulkActions;       // NEW
use Devfluxr\Gcmgr\Users\ProfileField;         // NEW
use Devfluxr\Gcmgr\Frontend\RenderHooks;
use Devfluxr\Gcmgr\Settings\SettingsPage;
use Devfluxr\Gcmgr\Shield\ShieldLite;

class Init {
    public static function get_services() : array {
        return [
            SettingsLinks::class,
            Enqueue::class,
            Admin::class,
            SettingsPage::class,
            RowActions::class,
            Notice::class,
            AutoTrustManager::class,
            GhostPublisher::class,
            FiltersUi::class,
            ListTableColumns::class,
            RenderHooks::class,
            ShieldLite::class,
            BulkActions::class,      // NEW
            ProfileField::class,     // NEW
        ];
    }
    public static function register_services() : void {
        foreach ( self::get_services() as $class ) {
            $service = new $class();
            if ( method_exists( $service, 'register' ) ) { $service->register(); }
        }
    }
}
