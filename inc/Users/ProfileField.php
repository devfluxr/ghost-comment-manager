<?php
namespace Devfluxr\Gcmgr\Users;

if ( ! defined( 'ABSPATH' ) ) { exit; }
use Devfluxr\Gcmgr\Comments\TrustService;

class ProfileField {

    public function register() : void {
        add_action( 'show_user_profile',  [ $this, 'render' ] );
        add_action( 'edit_user_profile',  [ $this, 'render' ] );
        add_action( 'personal_options_update', [ $this, 'save' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save' ] );
    }

    public function render( \WP_User $user ) : void {
        if ( ! current_user_can( 'moderate_comments' ) ) { return; }
        $checked = TrustService::is_trusted( (int) $user->ID );
        ?>
        <h2><?php esc_html_e( 'Ghost Comment Manager', 'ghost-comment-manager' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="gcmgr_trusted"><?php esc_html_e( 'Trusted Commenter', 'ghost-comment-manager' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gcmgr_trusted" name="gcmgr_trusted" value="1" <?php checked( $checked, true ); ?> />
                        <?php esc_html_e( 'Mark this user as Trusted (future comments auto-publish with a ghost flag).', 'ghost-comment-manager' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
        wp_nonce_field( 'gcmgr_save_trust_' . $user->ID, 'gcmgr_trust_nonce' );
    }

    public function save( int $user_id ) : void {
        if ( ! current_user_can( 'moderate_comments' ) ) { return; }
        if ( ! isset( $_POST['gcmgr_trust_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gcmgr_trust_nonce'] ) ), 'gcmgr_save_trust_' . $user_id ) ) {
            return;
        }
        $make_trusted = ! empty( $_POST['gcmgr_trusted'] );
        if ( $make_trusted ) {
            TrustService::trust( $user_id );
        } else {
            TrustService::untrust( $user_id );
        }
        // Let core show “Profile updated.”; our global notice is not required here.
    }
}
