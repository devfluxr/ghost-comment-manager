<?php
namespace Devfluxr\Gcmgr\Comments;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class RowActions
{
    public function register(): void
    {
        add_filter( 'comment_row_actions', [ $this, 'add_row_actions' ], 10, 2 );
        add_action( 'admin_post_gcmgr_trust',   [ $this, 'handle_trust' ] );
        add_action( 'admin_post_gcmgr_untrust', [ $this, 'handle_untrust' ] );
    }

    public function add_row_actions( array $actions, \WP_Comment $comment ): array
    {
        if ( ! current_user_can( 'moderate_comments' ) ) { return $actions; }

        $user_id = (int) $comment->user_id;
        if ( $user_id <= 0 ) { return $actions; }

        $trusted = TrustService::is_trusted( $user_id );
        $action  = $trusted ? 'gcmgr_untrust' : 'gcmgr_trust';
        $label   = $trusted
            ? esc_html__( 'Untrust User', 'ghost-comment-manager' )
            : '⭐ ' . esc_html__( 'Trust User', 'ghost-comment-manager' );

        $url = wp_nonce_url(
            add_query_arg(
                [
                    'action'  => $action,
                    'user_id' => $user_id,
                    'ref'     => 'edit-comments.php',
                ],
                admin_url( 'admin-post.php' )
            ),
            $action . '_' . $user_id
        );

        $actions['gcmgr_trust'] = sprintf(
            '<a href="%s" class="gcm-row-action">%s</a>',
            esc_url( $url ),
            esc_html( $label )
        );

        return $actions;
    }

    public function handle_trust(): void
    {
        $this->handle_toggle( 'gcmgr_trust', true );
    }

    public function handle_untrust(): void
    {
        $this->handle_toggle( 'gcmgr_untrust', false );
    }

    private function handle_toggle( string $action_key, bool $make_trusted ): void
    {
        if ( ! current_user_can( 'moderate_comments' ) ) {
            $this->redirect_error( 'perm' );
        }

        $user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
        $nonce_ok = isset( $_GET['_wpnonce'] ) && wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
            $action_key . '_' . $user_id
        );
        if ( ! $nonce_ok ) {
            $this->redirect_error( 'nonce' );
        }

        if ( $user_id <= 0 ) {
            $this->redirect_error( 'invalid_user' );
        }

        if ( $make_trusted ) {
            TrustService::trust( $user_id );
            do_action( 'gcmgr_user_trusted', $user_id );
            $msg = 'trusted';
        } else {
            TrustService::untrust( $user_id );
            do_action( 'gcmgr_user_untrusted', $user_id );
            $msg = 'untrusted';
        }

        $ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : 'edit-comments.php';
        $redirect_to = add_query_arg(
            [
                'gcmgr_'  => $msg,
                'uid'     => $user_id,
                'gcmgr_n' => wp_create_nonce( 'gcmgr_notice' ),
            ],
            admin_url( $ref )
        );

        wp_safe_redirect( $redirect_to );
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
