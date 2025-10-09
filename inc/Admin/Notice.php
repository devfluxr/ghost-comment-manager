<?php
namespace Devfluxr\Gcmgr\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class Notice {
    public function register() : void {
        add_action( 'admin_notices', [ $this, 'maybe_show_notice' ] );
    }

    public function maybe_show_notice() : void {
        if ( ! current_user_can( 'moderate_comments' ) ) {
            return;
        }

        // All our notices require a nonce in the URL to avoid processing arbitrary query vars.
        $nonce = isset( $_GET['gcmgr_n'] ) ? sanitize_text_field( wp_unslash( $_GET['gcmgr_n'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'gcmgr_notice' ) ) {
            return;
        }

        // Single user trust/untrust notices
        if ( ! empty( $_GET['gcmgr_'] ) ) {
            $gcm = sanitize_text_field( wp_unslash( $_GET['gcmgr_'] ) );

            if ( in_array( $gcm, [ 'trusted', 'untrusted' ], true ) ) {
                $user_id = isset( $_GET['uid'] ) ? (int) $_GET['uid'] : 0;
                $user    = $user_id ? get_user_by( 'id', $user_id ) : null;
                $name    = $user ? $user->display_name : ( '#' . $user_id );

                if ( $gcm === 'trusted' ) {
                    /* translators: 1: user display name, 2: user ID */
                    $msg = sprintf( esc_html__( 'User %1$s (ID %2$d) has been marked as Trusted.', 'ghost-comment-manager' ), esc_html( $name ), $user_id );
                } else {
                    /* translators: 1: user display name, 2: user ID */
                    $msg = sprintf( esc_html__( 'User %1$s (ID %2$d) has been untrusted.', 'ghost-comment-manager' ), esc_html( $name ), $user_id );
                }

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
                return;
            }

            if ( $gcm === 'ghost_confirmed' ) {
                $cid = isset( $_GET['cid'] ) ? (int) $_GET['cid'] : 0;
                /* translators: %d: comment ID */
                $msg = sprintf( esc_html__( 'Comment #%d confirmed (ghost removed).', 'ghost-comment-manager' ), $cid );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
                return;
            }
        }

        // Bulk actions notices
        if ( ! empty( $_GET['gcmgr_bulk'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_GET['gcmgr_bulk'] ) );
            $count  = isset( $_GET['gcmgr_bulk_count'] ) ? (int) $_GET['gcmgr_bulk_count'] : 0;

            if ( in_array( $action, [ 'gcmgr_trust_user', 'gcmgr_untrust_user' ], true ) ) {
                if ( $action === 'gcmgr_trust_user' ) {
                    /* translators: %d: number of users */
                    $msg = sprintf( esc_html__( 'Trusted %d user(s) from selected comments.', 'ghost-comment-manager' ), $count );
                } else {
                    /* translators: %d: number of users */
                    $msg = sprintf( esc_html__( 'Untrusted %d user(s) from selected comments.', 'ghost-comment-manager' ), $count );
                }
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
            }
        }
    }
}
