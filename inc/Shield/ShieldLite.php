<?php
namespace Devfluxr\Gcmgr\Shield;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Settings\Registry;
use Devfluxr\Gcmgr\Analytics\Metrics;

class ShieldLite
{
    public function register(): void
    {
        add_action('comment_form_after_fields', [$this, 'render_hidden_fields']);
        add_action('comment_form_logged_in_after', [$this, 'render_hidden_fields']);
        add_filter('preprocess_comment', [$this, 'preprocess'], 5);
        add_action('pre_comment_on_post', [$this, 'rate_limit'], 9, 1);
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
        // Recursion guard: if someone mis-hooked this method to our own filter, bail.
        if (function_exists('current_filter') && current_filter() === 'gcmgr_preprocess_comment') {
            return $commentdata;
        }

        if (empty($_POST['gcmgr_comment_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gcmgr_comment_nonce'])), 'gcmgr_comment_shield')) {
            wp_die(esc_html__('Security check failed. Please refresh and try again.', 'ghost-comment-manager'), 403);
        }

        $o = Registry::get();

        // Auto-close by age
        if ((int) $o['shield_auto_close_days'] > 0 && !empty($commentdata['comment_post_ID'])) {
            $post = get_post((int) $commentdata['comment_post_ID']);
            if ($post && $post->post_date_gmt) {
                $days = (time() - strtotime($post->post_date_gmt)) / DAY_IN_SECONDS;
                if ($days > (int) $o['shield_auto_close_days']) {
                    \Devfluxr\Gcmgr\Analytics\Metrics::bump('blocked_auto_close');
                    wp_die(esc_html__('Comments are closed for this post.', 'ghost-comment-manager'), 403);
                }
            }
        }

        // Honeypot
        if ((int) $o['shield_honeypot'] === 1 && !empty($_POST['gcmgr_hp'])) {
            Metrics::bump('blocked_honeypot');
            wp_die(esc_html__('Spam protection triggered.', 'ghost-comment-manager'), 403);
        }

        // Minimum submit time
        $min_secs = (int) $o['shield_min_submit_secs'];
        if ($min_secs > 0) {
            $start = isset($_POST['gcmgr_start']) ? (int) $_POST['gcmgr_start'] : 0;
            if ($start === 0 || (time() - $start) < $min_secs) {
                Metrics::bump('blocked_min_submit');
                wp_die(esc_html__('Please take a moment before submitting your comment.', 'ghost-comment-manager'), 429);
            }
        }

        // Length
        $content = isset($commentdata['comment_content']) ? (string) $commentdata['comment_content'] : '';
        $len = mb_strlen(wp_strip_all_tags($content));
        $min = (int) $o['shield_min_length'];
        $max = (int) $o['shield_max_length'];
        if ($min > 0 && $len < $min) {
            Metrics::bump('blocked_len_min');
            wp_die(esc_html__('Your comment is too short.', 'ghost-comment-manager'), 400);
        }
        if ($max > 0 && $len > $max) {
            Metrics::bump('blocked_len_max');
            wp_die(esc_html__('Your comment is too long.', 'ghost-comment-manager'), 400);
        }

        // Link limit
        $limit = (int) $o['shield_link_limit'];
        if ($limit > 0) {
            $links = preg_match_all('#https?://#i', $content);
            if ($links > $limit) {
                Metrics::bump('blocked_links');
                wp_die(esc_html__('Too many links in comment.', 'ghost-comment-manager'), 400);
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
                    wp_die(esc_html__('Your comment contains a blocked term.', 'ghost-comment-manager'), 400);
                }
            }
        }

        // Duplicate within N seconds (per IP)
        $dup_win = (int) $o['shield_dedupe_seconds'];
        if ($dup_win > 0) {
            $ip = $this->ip();
            $hash = md5($content);
            $key = 'gcmgr_last_' . $ip . '_hash';
            $prev = get_transient($key);
            if ($prev && $prev === $hash) {
                Metrics::bump('blocked_duplicate');
                wp_die(esc_html__('Duplicate comment detected.', 'ghost-comment-manager'), 429);
            }
            set_transient($key, $hash, $dup_win);
        }

        // 0.B — Allow PRO/Extensions to modify or short-circuit before final return.
        $ext = apply_filters('gcmgr_preprocess_comment', $commentdata);
        if (is_wp_error($ext)) {
            // Treat as a block; optional: bump a generic counter
            \Devfluxr\Gcmgr\Analytics\Metrics::bump('blocked_custom');
            wp_die(esc_html($ext->get_error_message()), 400);
        }

        if (is_array($ext)) {
            $commentdata = $ext;
        }

        return $commentdata;
    }

    public function rate_limit(int $post_id): void
    {
        $o = Registry::get();
        $ip = $this->ip();

        // per minute
        $pm = (int) $o['shield_rate_per_min'];
        if ($pm > 0) {
            $k = 'gcmgr_rate_m_' . $ip;
            $n = (int) get_transient($k);
            if ($n >= $pm) {
                Metrics::bump('blocked_rate_min');
                wp_die(esc_html__('Too many comments in a short time. Please slow down.', 'ghost-comment-manager'), 429);
            }
            set_transient($k, $n + 1, MINUTE_IN_SECONDS);
        }

        // per hour
        $ph = (int) $o['shield_rate_per_hour'];
        if ($ph > 0) {
            $k = 'gcmgr_rate_h_' . $ip;
            $n = (int) get_transient($k);
            if ($n >= $ph) {
                Metrics::bump('blocked_rate_hour');
                wp_die(esc_html__('Rate limit reached. Try again later.', 'ghost-comment-manager'), 429);
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
}
