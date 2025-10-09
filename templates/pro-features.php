<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$pro_active = class_exists('\GcmPro\Init') || defined('GCM_PRO_VERSION');
$envato_url = 'https://www.google.com/search?q=ghost+comment+manager+pro+site%3Acodecanyon.net';
?>
<div class="wrap gcm-wrap">
  <h1><?php esc_html_e('Pro Features','ghost-comment-manager'); ?></h1>

  <?php if ( $pro_active ) : ?>
    <div class="notice notice-success is-dismissible" style="margin-top:12px">
      <p><strong><?php esc_html_e('Great news! GCM PRO is installed and active.','ghost-comment-manager'); ?></strong></p>
    </div>
    <p><?php esc_html_e('Open the PRO dashboard to access Assignments, Analytics, Rules, Webhooks and more.','ghost-comment-manager'); ?></p>
    <p>
      <a class="button button-primary" href="<?php echo esc_url( admin_url('admin.php?page=gcm_pro') ); ?>">
        <?php esc_html_e('Open PRO','ghost-comment-manager'); ?>
      </a>
      <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=gcm_settings#gcm-pro') ); ?>">
        <?php esc_html_e('PRO Settings','ghost-comment-manager'); ?>
      </a>
    </p>
  <?php else : ?>
    <p class="gcm-sub">
      <?php esc_html_e('Upgrade to Ghost Comment Manager PRO on Envato for advanced workflow, automation, and analytics.','ghost-comment-manager'); ?>
    </p>

    <div class="gcm-pro-grid">
      <div class="gcm-pro-card gcm-pro-blue">
        <span class="gcm-pro-badge"><?php esc_html_e('Upgrade','ghost-comment-manager'); ?></span>
        <h3 class="gcm-pro-title"><?php esc_html_e('Trust Levels (Bronze/Silver/Gold)','ghost-comment-manager'); ?></h3>
      </div>
      <div class="gcm-pro-card gcm-pro-green">
        <span class="gcm-pro-badge"><?php esc_html_e('Upgrade','ghost-comment-manager'); ?></span>
        <h3 class="gcm-pro-title"><?php esc_html_e('Rules & Scoring Engine','ghost-comment-manager'); ?></h3>
      </div>
      <div class="gcm-pro-card gcm-pro-purple">
        <span class="gcm-pro-badge"><?php esc_html_e('Upgrade','ghost-comment-manager'); ?></span>
        <h3 class="gcm-pro-title"><?php esc_html_e('Team Assignments & Notes','ghost-comment-manager'); ?></h3>
      </div>
      <div class="gcm-pro-card gcm-pro-orange">
        <span class="gcm-pro-badge"><?php esc_html_e('Upgrade','ghost-comment-manager'); ?></span>
        <h3 class="gcm-pro-title"><?php esc_html_e('Analytics & CSV Export','ghost-comment-manager'); ?></h3>
      </div>
      <div class="gcm-pro-card gcm-pro-teal">
        <span class="gcm-pro-badge"><?php esc_html_e('Upgrade','ghost-comment-manager'); ?></span>
        <h3 class="gcm-pro-title"><?php esc_html_e('Role/Post-Type Overrides, Webhooks, Digests, Mute','ghost-comment-manager'); ?></h3>
      </div>
    </div>

    <p style="margin-top:16px">
      <a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url( $envato_url ); ?>">
        <?php esc_html_e('Buy PRO on Envato','ghost-comment-manager'); ?>
      </a>
    </p>
  <?php endif; ?>
</div>
