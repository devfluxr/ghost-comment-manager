<?php
namespace Devfluxr\Gcmgr\Comments;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Devfluxr\Gcmgr\Settings\Registry;
use Devfluxr\Gcmgr\Analytics\Metrics;

class GhostPublisher
{
    public const META_GHOST = '_gcmgr_ghost';

    public function register(): void
    {
        add_filter( 'pre_comment_approved', [ $this, 'maybe_auto_approve' ], 10, 2 );
        add_action( 'wp_insert_comment',    [ $this, 'maybe_mark_ghost' ], 10, 2 );
        add_action( 'admin_post_gcmgr_confirm_ghost', [ $this, 'confirm_ghost' ] );
        add_filter( 'comment_row_actions',  [ $this, 'add_confirm_action' ], 11, 2 );
    }

    public function maybe_auto_approve( $approved, array $commentdata )
    {
        $user_id = isset( $commentdata['user_ID'] ) ? (int) $commentdata['user_ID'] : 0;
        if ( $user_id > 0 && TrustService::is_trusted( $user_id ) ) {
            return 1; // approve immediately
        }
        return $approved;
    }

    /**
     * Accept both array (old expectation) and WP_Comment (actual wp_insert_comment payload).
     * Logic is unchanged—only normalization added.
     */
    public function maybe_mark_ghost( int $comment_id, $commentdata ): void
    {
        // Recursion guard: avoid loops if mis-hooked into our own override filter.
        if ( function_exists( 'current_filter' ) && current_filter() === 'gcmgr_maybe_ghost_decision' ) {
            return;
        }

        // Normalize inputs
        $user_id = 0;
        if ( is_array( $commentdata ) ) {
            $user_id = isset( $commentdata['user_ID'] ) ? (int) $commentdata['user_ID'] : 0;
        } elseif ( $commentdata instanceof \WP_Comment ) {
            $user_id = (int) $commentdata->user_id;
        }

        if ( $user_id <= 0 ) {
            return;
        }
        if ( ! TrustService::is_trusted( $user_id ) ) {
            return;
        }

        // Respect Akismet (or similar)
        $akismet = get_comment_meta( $comment_id, 'akismet_result', true );
        if ( $akismet && in_array( strtolower( (string) $akismet ), [ 'spam', 'discard' ], true ) ) {
            return;
        }

        $c = get_comment( $comment_id );
        if ( ! $c instanceof \WP_Comment ) {
            return;
        }
        if ( (int) $c->comment_approved !== 1 ) {
            return;
        }

        // Let extensions override ghosting decision.
        $override = apply_filters( 'gcmgr_maybe_ghost_decision', null, $commentdata, $comment_id, $user_id );
        if ( $override === 'skip' ) {
            return;
        }
        $force_ghost = ( $override === 'force' );

        // Exclude roles (publish normally, no ghost)
        $u        = get_user_by( 'id', $user_id );
        $opts     = Registry::get();
        $excluded = (array) $opts['role_exclusions'];
        if ( ! $force_ghost && $u && array_intersect( $excluded, (array) $u->roles ) ) {
            return;
        }

        update_comment_meta( $comment_id, self::META_GHOST, 1 );
        Metrics::bump( 'ghost_marked' );

        // Fire after ghost set.
        do_action( 'gcmgr_ghost_marked', $comment_id, $user_id );
    }

    public function add_confirm_action( array $actions, \WP_Comment $comment ): array
    {
        if ( ! current_user_can( 'moderate_comments' ) ) {
            return $actions;
        }
        if ( ! get_comment_meta( $comment->comment_ID, self::META_GHOST, true ) ) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                [
                    'action'     => 'gcmgr_confirm_ghost',
                    'comment_id' => $comment->comment_ID,
                    'ref'        => 'edit-comments.php',
                ],
                admin_url( 'admin-post.php' )
            ),
            'gcmgr_confirm_' . $comment->comment_ID
        );
        $actions['gcmgr_confirm'] = '<a href="' . esc_url( $url ) . '" class="gcm-row-action">' . esc_html__( 'Confirm (remove ghost)', 'ghost-comment-manager' ) . '</a>';
        return $actions;
    }

    public function confirm_ghost(): void
    {
        if ( ! current_user_can( 'moderate_comments' ) ) {
            $this->redirect_error( 'perm' );
        }
        $comment_id = isset( $_GET['comment_id'] ) ? (int) $_GET['comment_id'] : 0;

        $nonce_ok = isset( $_GET['_wpnonce'] ) && wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
            'gcmgr_confirm_' . $comment_id
        );
        if ( ! $nonce_ok ) {
            $this->redirect_error( 'nonce' );
        }
        if ( $comment_id <= 0 ) {
            $this->redirect_error( 'invalid_comment' );
        }

        delete_comment_meta( $comment_id, self::META_GHOST );
        Metrics::bump( 'ghost_confirmed' );

        // Fire after confirm.
        do_action( 'gcmgr_ghost_confirmed', $comment_id );

        $ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : 'edit-comments.php';
        $redirect = add_query_arg(
            [
                'gcmgr_'  => 'ghost_confirmed',
                'cid'     => $comment_id,
                'gcmgr_n' => wp_create_nonce( 'gcmgr_notice' ),
            ],
            admin_url( $ref )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    private function redirect_error( string $code, string $ref = 'edit-comments.php' ): void
    {
        $url = add_query_arg(
            [
                'gcmgr_err' => $code,
                'gcmgr_n'   => wp_create_nonce( 'gcmgr_notice' ),
            ],
            admin_url( $ref )
        );
        wp_safe_redirect( $url );
        exit;
    }
}
