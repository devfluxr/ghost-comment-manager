<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var array $data_var */ $d = $data_var; ?>
<div class="wrap gcm-wrap">
  <h1>Ghost Comment Manager</h1>
  <p class="gcm-sub"><?php esc_html_e('Trusted comments auto-publish and are flagged for moderators until confirmed.','ghost-comment-manager');?></p>

  <div class="gcm-cards">
  <div class="gcm-card gcm-card-blue">
    <p class="gcm-k"><?php esc_html_e( 'Trusted Users (active)', 'ghost-comment-manager' ); ?></p>
    <p class="gcm-v"><?php echo (int) $d['trusted_total']; ?></p>
  </div>

  <div class="gcm-card gcm-card-green">
    <p class="gcm-k"><?php esc_html_e( 'Ghost Comments Awaiting Review', 'ghost-comment-manager' ); ?></p>
    <p class="gcm-v"><?php echo (int) $d['ghost_pending']; ?></p>
  </div>

  <div class="gcm-card gcm-card-purple">
    <p class="gcm-k"><?php esc_html_e( 'Users Auto-Trusted (all-time)', 'ghost-comment-manager' ); ?></p>
    <p class="gcm-v"><?php echo (int) $d['auto_trusted']; ?></p>
  </div>

  <div class="gcm-card gcm-card-orange">
    <p class="gcm-k"><?php esc_html_e( 'Comments Ghosted (all-time)', 'ghost-comment-manager' ); ?></p>
    <p class="gcm-v"><?php echo (int) $d['ghost_marked']; ?></p>
  </div>

  <div class="gcm-card gcm-card-teal">
    <p class="gcm-k"><?php esc_html_e( 'Ghost Comments Confirmed (all-time)', 'ghost-comment-manager' ); ?></p>
    <p class="gcm-v"><?php echo (int) $d['ghost_confirmed']; ?></p>
  </div>
</div>


  <div class="gcm-grid">
    <h2><?php esc_html_e('Shield Lite — Blocks by Reason','ghost-comment-manager');?></h2>
    <table class="gcm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Reason','ghost-comment-manager');?></th>
          <th><?php esc_html_e('Count','ghost-comment-manager');?></th>
        </tr>
      </thead>
      <tbody>
        <?php
          $rows = [
            'Honeypot'             => $d['blocked']['honeypot'],
            'Minimum submit time'  => $d['blocked']['min_submit'],
            'Rate (per minute)'    => $d['blocked']['rate_min'],
            'Rate (per hour)'      => $d['blocked']['rate_hour'],
            'Too many links'       => $d['blocked']['links'],
            'Keyword/regex'        => $d['blocked']['blocklist'],
            'Auto-close by age'    => $d['blocked']['auto_close'],
            'Too short'            => $d['blocked']['len_min'],
            'Too long'             => $d['blocked']['len_max'],
            'Duplicate'            => $d['blocked']['duplicate'],
          ];
          foreach ($rows as $label => $val) {
            echo '<tr><td>'.esc_html($label).'</td><td>'.(int)$val.'</td></tr>';
          }
        ?>
      </tbody>
    </table>
  </div>

  <p class="gcm-foot">
    <?php
      /* translators: %1$s: URL to plugin Settings page */
      $format = __( 'Need to change behavior? Go to <a href="%1$s">Settings</a>.', 'ghost-comment-manager' );
      printf(
        wp_kses_post( $format ),
        esc_url( admin_url( 'admin.php?page=gcmgr_settings' ) )
      );
    ?>
  </p>


</div>
