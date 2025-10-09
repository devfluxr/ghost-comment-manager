<?php
namespace Devfluxr\Gcmgr\Comments;

if ( ! defined( 'ABSPATH' ) ) { exit; }
class BulkActions
{

    public function register(): void
    {
        add_filter('bulk_actions-edit-comments', [$this, 'register_bulk']);
        add_filter('handle_bulk_actions-edit-comments', [$this, 'handle_bulk'], 10, 3);
    }

    public function register_bulk(array $actions): array
    {
        if (current_user_can('moderate_comments')) {
            $actions['gcmgr_trust_user'] = __('Trust user of selected comments', 'ghost-comment-manager');
            $actions['gcmgr_untrust_user'] = __('Untrust user of selected comments', 'ghost-comment-manager');
        }
        return $actions;
    }

    /**
     * @param string $redirect_to
     * @param string $doaction
     * @param array  $comment_ids
     * @return string
     */
    public function handle_bulk(string $redirect_to, string $doaction, array $comment_ids): string
    {
        if (!current_user_can('moderate_comments')) {
            return $redirect_to;
        }

        if (!in_array($doaction, ['gcmgr_trust_user', 'gcmgr_untrust_user'], true)) {
            return $redirect_to;
        }

        // Unique user IDs from the selected comments (skip guests)
        $user_ids = [];
        foreach ($comment_ids as $cid) {
            $c = get_comment((int) $cid);
            if ($c instanceof \WP_Comment && (int) $c->user_id > 0) {
                $user_ids[(int) $c->user_id] = true;
            }
        }
        $user_ids = array_keys($user_ids);
        $changed = 0;

        foreach ($user_ids as $uid) {
            if ($doaction === 'gcmgr_trust_user') {
                if (!TrustService::is_trusted($uid)) {
                    TrustService::trust($uid);
                    $changed++;
                }
            } else {
                if (TrustService::is_trusted($uid)) {
                    TrustService::untrust($uid);
                    $changed++;
                }
            }
        }

        // Add a query arg so our Notice class can show a message
        $redirect_to = add_query_arg([
            'gcmgr_bulk' => $doaction,
            'gcmgr_bulk_count' => $changed,
            'gcmgr_n' => wp_create_nonce('gcmgr_notice'),
        ], $redirect_to);

        return $redirect_to;

    }
}
