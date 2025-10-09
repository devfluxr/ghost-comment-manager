<?php
namespace Devfluxr\Gcmgr\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Comments\GhostPublisher;
use Devfluxr\Gcmgr\Comments\TrustService;

class ListTableColumns
{
    public function register(): void
    {
        add_filter('manage_edit-comments_columns', [$this, 'add_col']);
        add_action('manage_comments_custom_column', [$this, 'render_col'], 10, 2);
        // No inline CSS here – styles live in assets/css/admin.css
    }

    public function add_col(array $cols): array
    {
        $cols['gcmgr_status'] = __('GCM', 'ghost-comment-manager');
        return $cols;
    }

    public function render_col(string $column, $comment_ID): void
    {
        if ($column !== 'gcmgr_status') {
            return;
        }
        $c = get_comment($comment_ID);
        if (!$c instanceof \WP_Comment) {
            echo '—';
            return;
        }

        $badges = [];
        $user_id = (int) $c->user_id;
        if ($user_id > 0 && \Devfluxr\Gcmgr\Comments\TrustService::is_trusted($user_id)) {
            $badges[] = '<span class="gcm-badge gcm-badge-trusted">' . esc_html__('Trusted User', 'ghost-comment-manager') . '</span>';
        }
        if (get_comment_meta($comment_ID, \Devfluxr\Gcmgr\Comments\GhostPublisher::META_GHOST, true)) {
            $badges[] = '<span class="gcm-badge gcm-badge-ghost">👻 ' . esc_html__('Ghost', 'ghost-comment-manager') . '</span>';
        }

        $html = implode(' ', $badges);
        echo wp_kses_post($html); // escape output
    }

}
