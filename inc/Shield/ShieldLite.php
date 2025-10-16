<?php
namespace Devfluxr\Gcmgr\Shield;

if (!defined('ABSPATH')) {
    exit;
}

use Devfluxr\Gcmgr\Settings\Registry;
use Devfluxr\Gcmgr\Analytics\Metrics;

class ShieldLite
{
    public function register(): void
    {
        add_action('comment_form_after_fields', [$this, 'render_hidden_fields']);
        add_action('comment_form_logged_in_after', [$this, 'render_hidden_fields']);

        // Early gate: sanitize/validate before Core inserts.
        add_filter('preprocess_comment', [$this, 'preprocess'], 5);

        // Rate limiting prior to insert.
        add_action('pre_comment_on_post', [$this, 'rate_limit'], 9, 1);

        // Fallback: if Core still detects a duplicate, convert its wp_die() into our soft redirect.
        add_filter('wp_allow_comment', [$this, 'catch_duplicate_from_core'], 10, 2);
    }

    public function render_hidden_fields(): void
    {
        $o = Registry::get();
        $ts = time();

        wp_nonce_field('gcmgr_comment_shield', 'gcmgr_comment_nonce');
        echo '<input type="hidden" name="gcmgr_start" value="' . esc_attr($ts) . '" />';

        if ((int) $o['shield_honeypot'] === 1) {
            echo '<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">';
            echo '<label>' . esc_html__('Leave this field empty', 'ghost-comment-manager') . '</label>';
            echo '<input type="text" name="gcmgr_hp" value="" tabindex="-1" autocomplete="off" />';
            echo '</div>';
        }
    }

    public function preprocess(array $commentdata): array
    {
        // Nonce
        if (
            empty($_POST['gcmgr_comment_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['gcmgr_comment_nonce'])),
                'gcmgr_comment_shield'
            )
        ) {
            $this->reject(esc_html__('Security check failed. Please refresh and try again.', 'ghost-comment-manager'));
        }

        $o = Registry::get();

        // Auto-close by age
        if ((int) $o['shield_auto_close_days'] > 0 && !empty($commentdata['comment_post_ID'])) {
            $post = get_post((int) $commentdata['comment_post_ID']);
            if ($post && $post->post_date_gmt) {
                $days = (time() - strtotime($post->post_date_gmt)) / DAY_IN_SECONDS;
                if ($days > (int) $o['shield_auto_close_days']) {
                    Metrics::bump('blocked_auto_close');
                    $this->reject(esc_html__('Comments are closed for this post.', 'ghost-comment-manager'));
                }
            }
        }

        // Honeypot
        if ((int) $o['shield_honeypot'] === 1 && !empty($_POST['gcmgr_hp'])) {
            Metrics::bump('blocked_honeypot');
            $this->reject(esc_html__('Spam protection triggered.', 'ghost-comment-manager'));
        }

        // Minimum submit time
        $min_secs = (int) $o['shield_min_submit_secs'];
        if ($min_secs > 0) {
            $start = isset($_POST['gcmgr_start']) ? (int) $_POST['gcmgr_start'] : 0;
            if ($start === 0 || (time() - $start) < $min_secs) {
                Metrics::bump('blocked_min_submit');
                $this->reject(esc_html__('Please take a moment before submitting your comment.', 'ghost-comment-manager'));
            }
        }

        // Length checks
        $content = isset($commentdata['comment_content']) ? (string) $commentdata['comment_content'] : '';
        $len = mb_strlen(wp_strip_all_tags($content));
        $min = (int) $o['shield_min_length'];
        $max = (int) $o['shield_max_length'];

        if ($min > 0 && $len < $min) {
            Metrics::bump('blocked_len_min');
            $this->reject(esc_html__('Your comment is too short.', 'ghost-comment-manager'));
        }
        if ($max > 0 && $len > $max) {
            Metrics::bump('blocked_len_max');
            $this->reject(esc_html__('Your comment is too long.', 'ghost-comment-manager'));
        }

        // Link limit
        $limit = (int) $o['shield_link_limit'];
        if ($limit > 0) {
            $links = preg_match_all('#https?://#i', $content);
            if ($links > $limit) {
                Metrics::bump('blocked_links');
                $this->reject(esc_html__('Too many links in comment.', 'ghost-comment-manager'));
            }
        }

        // Keyword/regex blocklist
        $blocklist = trim((string) $o['shield_blocklist']);
        if ($blocklist !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $blocklist);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $hit = false;
                if ($line[0] === '/' && substr($line, -1) === '/') {
                    $hit = @preg_match($line, $content) === 1;
                } elseif (preg_match('/^\/.+\/[a-z]*$/i', $line)) {
                    $hit = @preg_match($line, $content) === 1;
                } else {
                    $hit = (stripos($content, $line) !== false);
                }

                if ($hit) {
                    Metrics::bump('blocked_blocklist');
                    $this->reject(esc_html__('Blocked content detected.', 'ghost-comment-manager'));
                }
            }
        }

        // Duplicate, pre-Core
        if ($this->is_duplicate_comment($commentdata)) {
            Metrics::bump('blocked_duplicate');
            $this->reject(esc_html__('Duplicate comment detected; it looks like you’ve already said that.', 'ghost-comment-manager'));
        }

        // Extension hook
        $ext = apply_filters('gcmgr_preprocess_comment', $commentdata);
        if (is_wp_error($ext)) {
            Metrics::bump('blocked_custom');
            $this->reject(esc_html($ext->get_error_message()));
        }
        if (is_array($ext)) {
            $commentdata = $ext;
        }

        return $commentdata;
    }

    // Convert Core’s duplicate error into a soft redirect
    public function catch_duplicate_from_core($maybe_approved, array $commentdata)
    {
        if (is_wp_error($maybe_approved) && $maybe_approved->get_error_code() === 'comment_duplicate') {
            Metrics::bump('blocked_duplicate');
            $this->reject(esc_html__('Duplicate comment detected; it looks like you’ve already said that.', 'ghost-comment-manager'));
        }
        return $maybe_approved;
    }

    private function is_duplicate_comment(array $commentdata): bool
    {
        global $wpdb;

        $post_id = isset($commentdata['comment_post_ID']) ? (int) $commentdata['comment_post_ID'] : 0;
        $author = isset($commentdata['comment_author']) ? (string) $commentdata['comment_author'] : '';
        $email = isset($commentdata['comment_author_email']) ? (string) $commentdata['comment_author_email'] : '';
        $content = isset($commentdata['comment_content']) ? (string) $commentdata['comment_content'] : '';

        if ($post_id <= 0 || $content === '') {
            return false;
        }

        $dupe = $wpdb->get_var(
            $wpdb->prepare(
                "
            SELECT comment_ID
            FROM {$wpdb->comments}
            WHERE comment_post_ID = %d
              AND ( comment_author = %s OR comment_author_email = %s )
              AND comment_content = %s
            LIMIT 1
            ",
                $post_id,
                $author,
                $email,
                $content
            )
        );

        return !empty($dupe);
    }


    public function rate_limit(int $post_id): void
    {
        $o = Registry::get();
        $ip = $this->ip();

        // per-minute
        $pm = (int) $o['shield_rate_per_min'];
        if ($pm > 0) {
            $k = 'gcmgr_rate_m_' . $ip;
            $n = (int) get_transient($k);
            if ($n >= $pm) {
                Metrics::bump('blocked_rate_min');
                $this->reject(esc_html__('Too many comments in a short time. Please slow down.', 'ghost-comment-manager'));
            }
            set_transient($k, $n + 1, MINUTE_IN_SECONDS);
        }

        // per-hour
        $ph = (int) $o['shield_rate_per_hour'];
        if ($ph > 0) {
            $k = 'gcmgr_rate_h_' . $ip;
            $n = (int) get_transient($k);
            if ($n >= $ph) {
                Metrics::bump('blocked_rate_hour');
                $this->reject(esc_html__('Rate limit reached. Try again later.', 'ghost-comment-manager'));
            }
            set_transient($k, $n + 1, HOUR_IN_SECONDS);
        }
    }

    private function ip(): string
    {
        $raw = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $validated = filter_var($raw, FILTER_VALIDATE_IP);
        $ip = $validated ? $validated : '0.0.0.0';
        return preg_replace('/[^0-9a-fA-F:\.]/', '', (string) $ip);
    }

    private function reject(string $message): void
    {
        $ip = $this->ip();
        $key = 'gcmgr_flash_' . md5((string) $ip);
        set_transient($key, $message, MINUTE_IN_SECONDS * 5);

        $target = wp_get_referer();
        if (!$target && !empty($_POST['comment_post_ID'])) {
            $target = get_permalink((int) $_POST['comment_post_ID']);
        }
        if (!$target) {
            $target = home_url('/');
        }

        $target = add_query_arg('gcmgr_notice', '1', $target);
        $target .= '#respond';

        wp_safe_redirect($target, 302);
        exit;
    }
}
