<?php
namespace Devfluxr\Gcmgr\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Devfluxr\Gcmgr\Comments\GhostPublisher;
use Devfluxr\Gcmgr\Settings\Registry;

class RenderHooks
{
    public function register(): void
    {
        // Show a ghost wrapper for moderators (visual only).
        add_filter( 'comment_text', [ $this, 'maybe_wrap_ghost' ], 9, 3 );

        // Friendly inline message if ShieldLite bounced a submission.
        add_action( 'comment_form_before', [ $this, 'maybe_show_notice' ] );

        // Hide ghosted comments from public users until confirmed.
        add_filter( 'comments_array', [ $this, 'hide_ghosts_from_public' ], 10, 1 );
    }

    /**
     * Wrap ghosted comments for moderators only (visual indicator).
     *
     * @param string           $comment_text
     * @param \WP_Comment|null $comment
     * @param array            $args
     * @return string
     */
    public function maybe_wrap_ghost( $comment_text, $comment = null, $args = [] )
    {
        if ( ! current_user_can( 'moderate_comments' ) ) {
            return $comment_text;
        }
        if ( ! ( $comment instanceof \WP_Comment ) ) {
            return $comment_text;
        }
        if ( ! get_comment_meta( $comment->comment_ID, GhostPublisher::META_GHOST, true ) ) {
            return $comment_text;
        }

        $o     = Registry::get();
        $icon  = isset( $o['indicator_icon'] ) ? (string) $o['indicator_icon'] : '👻';
        $title = $icon . ' ' . __( 'Ghost (visible only to moderators)', 'ghost-comment-manager' );
        return '<div class="gcm-ghost-wrap" title="' . esc_attr( $title ) . '">' . $comment_text . '</div>';
    }

    /**
     * Hide ghosted comments from public (non-moderator) visitors.
     * Moderators/Admins still see them so they can confirm.
     *
     * @param array<int,\WP_Comment> $comments
     * @return array<int,\WP_Comment>
     */
    public function hide_ghosts_from_public( array $comments ) : array
    {
        if ( current_user_can( 'moderate_comments' ) ) {
            return $comments;
        }
        return array_values( array_filter( $comments, function ( $c ) {
            if ( ! ( $c instanceof \WP_Comment ) ) {
                return true;
            }
            $is_ghost = get_comment_meta( $c->comment_ID, GhostPublisher::META_GHOST, true );
            return empty( $is_ghost ); // keep only non-ghosts for public
        } ) );
    }

    /** Inline notice renderer (reads the transient set by ShieldLite::reject) */
    public function maybe_show_notice(): void
    {
        if ( is_admin() ) {
            return;
        }
        if ( empty( $_GET['gcmgr_notice'] ) ) {
            return;
        }

        // Same keying scheme as ShieldLite::reject().
        $ip_raw = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip     = filter_var( $ip_raw, FILTER_VALIDATE_IP ) ?: '0.0.0.0';
        $key    = 'gcmgr_flash_' . md5( (string) $ip );

        $msg = get_transient( $key );
        if ( ! $msg ) {
            return;
        }

        echo '<div class="gcmgr-notice" role="alert" aria-live="polite">' . esc_html( $msg ) . '</div>';
        delete_transient( $key );
    }
}
