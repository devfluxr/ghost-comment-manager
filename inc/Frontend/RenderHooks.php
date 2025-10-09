<?php
namespace Devfluxr\Gcmgr\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Comments\GhostPublisher;
use Devfluxr\Gcmgr\Settings\Registry;

class RenderHooks {
    public function register() : void {
        // Wrap ghosted comments for moderators (frontend only)
        add_filter( 'comment_text', [ $this, 'maybe_wrap_ghost' ], 9, 2 );
    }

    public function maybe_wrap_ghost( string $comment_text, \WP_Comment $comment ) : string {
        if ( ! current_user_can('moderate_comments') ) { return $comment_text; }
        if ( ! get_comment_meta( $comment->comment_ID, GhostPublisher::META_GHOST, true ) ) { return $comment_text; }

        $o = Registry::get();
        $icon = esc_html( $o['indicator_icon'] );
        return '<div class="gcm-ghost-wrap" title="'.esc_attr($icon.' Ghost (visible only to moderators)').'">'.$comment_text.'</div>';
    }
}
