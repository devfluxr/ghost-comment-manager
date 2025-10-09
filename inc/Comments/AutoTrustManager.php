<?php
namespace Devfluxr\Gcmgr\Comments;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Settings\Registry;

class AutoTrustManager {
    public function register() : void {
        add_action( 'comment_unapproved_to_approved', [ $this, 'maybe_trust_on_approval' ] );
        add_action( 'comment_approved_to_approved',   [ $this, 'maybe_trust_on_approval' ] );
        add_action( 'wp_set_comment_status',          [ $this, 'maybe_trust_on_transition' ], 10, 2 );
    }

    public function maybe_trust_on_approval( \WP_Comment $comment ) : void {
        $this->increment_and_check( $comment );
    }

    public function maybe_trust_on_transition( int $comment_id, string $status ) : void {
        if ( $status !== 'approve' ) { return; }
        $comment = get_comment( $comment_id );
        if ( $comment instanceof \WP_Comment ) {
            $this->increment_and_check( $comment );
        }
    }

private function increment_and_check( \WP_Comment $comment ) : void {
    $user_id = (int) $comment->user_id;
    if ( $user_id <= 0 ) { return; }

    $opts = Registry::get();
    $threshold = (int) $opts['auto_trust_threshold'];
    if ( $threshold <= 0 ) { return; }

    $approved_count = get_user_meta( $user_id, '_gcmgr_approved_count', true );
    $approved_count = (int) $approved_count + 1;
    update_user_meta( $user_id, '_gcmgr_approved_count', $approved_count );

    if ( $approved_count >= $threshold && ! TrustService::is_trusted( $user_id ) ) {
        TrustService::trust( $user_id );
        \Devfluxr\Gcmgr\Analytics\Metrics::bump('auto_trusted');
    }
}

}
