<?php
namespace Devfluxr\Gcmgr\Comments;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FiltersUi
{
    public function register(): void
    {
        add_action( 'restrict_manage_comments', [ $this, 'toolbar_dropdown' ] );
        add_filter( 'comment_status_links',     [ $this, 'add_views_links' ] );
        add_action( 'pre_get_comments',         [ $this, 'apply_filters' ] );
    }

    public function toolbar_dropdown( $post_type ): void
    {
        wp_nonce_field( 'gcmgr_filters', 'gcmgr_filters_nonce' );

        if ( ! current_user_can( 'moderate_comments' ) ) { return; }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'edit-comments' ) { return; }

        $nonce_ok = ( isset( $_GET['gcmgr_filters_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['gcmgr_filters_nonce'] ) ), 'gcmgr_filters' ) );
        $current  = ( $nonce_ok && isset( $_GET['gcmgr_view'] ) ) ? sanitize_text_field( wp_unslash( $_GET['gcmgr_view'] ) ) : '';
        ?>
        <label class="screen-reader-text" for="gcmgr_view"><?php esc_html_e( 'GCM View', 'ghost-comment-manager' ); ?></label>
        <select name="gcmgr_view" id="gcmgr_view">
            <option value="" <?php selected( $current, '' ); ?>>
                <?php esc_html_e( 'All (no GCM filter)', 'ghost-comment-manager' ); ?>
            </option>
            <option value="pending_new" <?php selected( $current, 'pending_new' ); ?>>
                <?php esc_html_e( 'Pending (New Users)', 'ghost-comment-manager' ); ?>
            </option>
            <option value="ghost" <?php selected( $current, 'ghost' ); ?>>
                <?php esc_html_e( 'Ghost (Trusted)', 'ghost-comment-manager' ); ?>
            </option>
        </select>
        <?php
    }

    public function add_views_links( array $status_links ): array
    {
        if ( ! current_user_can( 'moderate_comments' ) ) { return $status_links; }

        $base   = admin_url( 'edit-comments.php' );
        $nonce  = wp_create_nonce( 'gcmgr_filters' );
        $nonce_ok = ( isset( $_GET['gcmgr_filters_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['gcmgr_filters_nonce'] ) ), 'gcmgr_filters' ) );
        $current  = ( $nonce_ok && isset( $_GET['gcmgr_view'] ) ) ? sanitize_text_field( wp_unslash( $_GET['gcmgr_view'] ) ) : '';

        $links = [
            'gcmgr_pending_new' => [
                'label' => __( 'Pending (New Users)', 'ghost-comment-manager' ),
                'href'  => add_query_arg( [ 'gcmgr_view' => 'pending_new', 'gcmgr_filters_nonce' => $nonce ], $base ),
                'on'    => ( $current === 'pending_new' ),
            ],
            'gcmgr_ghost' => [
                'label' => __( 'Ghost (Trusted)', 'ghost-comment-manager' ),
                'href'  => add_query_arg( [ 'gcmgr_view' => 'ghost', 'gcmgr_filters_nonce' => $nonce ], $base ),
                'on'    => ( $current === 'ghost' ),
            ],
        ];

        foreach ( $links as $key => $l ) {
            $class = $l['on'] ? ' class="current"' : '';
            $status_links[ $key ] = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url( $l['href'] ),
                $class,
                esc_html( $l['label'] )
            );
        }
        return $status_links;
    }

    /**
     * - pending_new: comments "hold" by users who are NOT trusted
     * - ghost: approved comments with _gcmgr_ghost = 1
     */
    public function apply_filters( \WP_Comment_Query $query ): void
    {
        if ( ! is_admin() || ! current_user_can( 'moderate_comments' ) ) { return; }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'edit-comments' ) { return; }

        if ( empty( $_GET['gcmgr_filters_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['gcmgr_filters_nonce'] ) ), 'gcmgr_filters' ) ) {
            return;
        }

        $view = isset( $_GET['gcmgr_view'] ) ? sanitize_text_field( wp_unslash( $_GET['gcmgr_view'] ) ) : '';

        if ( $view === 'pending_new' ) {
            $query->query_vars['status'] = 'hold';
            add_filter( 'comments_clauses', [ $this, 'pending_new_clauses' ] );

        } elseif ( $view === 'ghost' ) {
            // OLD BEHAVIOR KEPT: only approved + meta = 1 (int)
            $query->query_vars['status'] = 'approve';
            $meta_query = isset( $query->query_vars['meta_query'] ) && is_array( $query->query_vars['meta_query'] )
                ? $query->query_vars['meta_query']
                : [];
            $meta_query[] = [
                'key'   => GhostPublisher::META_GHOST,
                'value' => 1,
            ];
            $query->query_vars['meta_query'] = $meta_query;
        }
    }

    /**
     * Users NOT trusted → no _gcmgr_trusted or falsy
     */
public function pending_new_clauses( array $clauses ): array
{
    global $wpdb;

    $um = $wpdb->usermeta;
    $c  = $wpdb->comments;

    // Join to usermeta to detect "not trusted".
    $clauses['join']  .= " LEFT JOIN {$um} AS gcm_um ON ( {$c}.user_id = gcm_um.user_id AND gcm_um.meta_key = '_gcmgr_trusted' ) ";

    // Qualify any unqualified "user_id = X" that Core may have injected (e.g., "Mine" view).
    // This prevents "Column 'user_id' in WHERE is ambiguous" after our JOIN.
    if ( isset( $clauses['where'] ) && is_string( $clauses['where'] ) ) {
        // Replace whole-word user_id only (not table-qualified), keep spacing intact.
        $clauses['where'] = preg_replace(
            '/(?<![.\w])user_id(?!\s*\.)\s*=/',
            "{$c}.user_id =",
            $clauses['where']
        );
    }

    // Only not-trusted users: meta row missing or falsy.
    $clauses['where'] .= " AND ( gcm_um.umeta_id IS NULL OR gcm_um.meta_value = '' OR gcm_um.meta_value = '0' ) ";

    // Remove this filter after applying once for the current query.
    remove_filter( 'comments_clauses', [ $this, 'pending_new_clauses' ] );

    return $clauses;
}

}
