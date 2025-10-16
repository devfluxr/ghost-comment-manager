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

        // ERROR NOTICES (no nonce required so we can report nonce failure)
        if ( ! empty( $_GET['gcmgr_err'] ) ) {
            $err = sanitize_text_field( wp_unslash( $_GET['gcmgr_err'] ) );

            switch ( $err ) {
                case 'perm':
                    $msg = esc_html__( 'Action failed: insufficient permissions.', 'ghost-comment-manager' );
                    break;
                case 'nonce':
                    $msg = esc_html__( 'Action failed: security check failed (invalid or missing nonce).', 'ghost-comment-manager' );
                    break;
                case 'invalid_user':
                    $msg = esc_html__( 'Action failed: invalid user.', 'ghost-comment-manager' );
                    break;
                case 'invalid_comment':
                    $msg = esc_html__( 'Action failed: invalid comment.', 'ghost-comment-manager' );
                    break;
                default:
                    $msg = esc_html__( 'Action failed due to an unknown error.', 'ghost-comment-manager' );
            }

            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
            // continue; success/bulk notices require a valid nonce below.
        }

        // SUCCESS / BULK NOTICES REQUIRE A VALID NONCE
        $nonce = isset( $_GET['gcmgr_n'] ) ? sanitize_text_field( wp_unslash( $_GET['gcmgr_n'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'gcmgr_notice' ) ) {
            return;
        }

        // SUCCESS NOTICES (single)
        if ( ! empty( $_GET['gcmgr_'] ) ) {
            $gcm = sanitize_text_field( wp_unslash( $_GET['gcmgr_'] ) );

            if ( in_array( $gcm, [ 'trusted', 'untrusted' ], true ) ) {
                $user_id = isset( $_GET['uid'] ) ? (int) $_GET['uid'] : 0;
                $user    = $user_id ? get_user_by( 'id', $user_id ) : null;
                $name    = $user ? $user->display_name : ( '#' . $user_id );

                if ( $gcm === 'trusted' ) {
                    // translators: 1: user display name, 2: user ID.
                    $pattern = __( 'User %1$s (ID %2$d) has been marked as Trusted.', 'ghost-comment-manager' );
                } else {
                    // translators: 1: user display name, 2: user ID.
                    $pattern = __( 'User %1$s (ID %2$d) has been untrusted.', 'ghost-comment-manager' );
                }
                $msg = sprintf( $pattern, $name, $user_id );

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
                return;
            }

            if ( $gcm === 'ghost_confirmed' ) {
                $cid = isset( $_GET['cid'] ) ? (int) $_GET['cid'] : 0;
                // translators: %d: comment ID.
                $pattern = __( 'Comment #%d confirmed (ghost removed).', 'ghost-comment-manager' );
                $msg     = sprintf( $pattern, $cid );

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
                return;
            }
        }

        // BULK NOTICES
        if ( ! empty( $_GET['gcmgr_bulk'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_GET['gcmgr_bulk'] ) );
            $count  = isset( $_GET['gcmgr_bulk_count'] ) ? (int) $_GET['gcmgr_bulk_count'] : 0;

            if ( in_array( $action, [ 'gcmgr_trust_user', 'gcmgr_untrust_user' ], true ) ) {
                if ( $action === 'gcmgr_trust_user' ) {
                    // translators: %d: number of users.
                    $pattern = __( 'Trusted %d user(s) from selected comments.', 'ghost-comment-manager' );
                } else {
                    // translators: %d: number of users.
                    $pattern = __( 'Untrusted %d user(s) from selected comments.', 'ghost-comment-manager' );
                }
                $msg = sprintf( $pattern, $count );

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
            }
        }
    }
}
