<?php
namespace Devfluxr\Gcmgr\Comments;

class TrustService
{
    public const META_TRUSTED = '_gcmgr_trusted';

    public static function is_trusted(int $user_id): bool {
        if ($user_id <= 0) { return false; }
        return (bool) get_user_meta($user_id, self::META_TRUSTED, true);
    }

    public static function trust(int $user_id): void {
        if ($user_id > 0) {
            update_user_meta($user_id, self::META_TRUSTED, 1);
            do_action('gcmgr_user_trusted', $user_id);
        }
    }

    public static function untrust(int $user_id): void {
        if ($user_id > 0) {
            delete_user_meta($user_id, self::META_TRUSTED);
            do_action('gcmgr_user_untrusted', $user_id);
        }
    }
}
