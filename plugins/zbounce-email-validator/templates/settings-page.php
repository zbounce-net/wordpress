<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap zb-email-validator-settings">
    <h1><?php esc_html_e( 'ZBounce Email Validator Settings', 'zbounce-email-validator' ); ?></h1>
    <?php settings_errors( 'zb_email_validator_settings' ); ?>
    <p><?php esc_html_e( 'Buy verification credits on ZBounce, then paste the API key you receive after payment. The plugin itself is free; remote checks use your credits.', 'zbounce-email-validator' ); ?></p>
    <p><a href="<?php echo esc_url( 'https://zbounce.net/pricing.html' ); ?>"><?php esc_html_e( 'Buy credits / get an API key', 'zbounce-email-validator' ); ?></a></p>
    <section class="zb-settings-tester" aria-label="<?php esc_attr_e( 'Check an email address', 'zbounce-email-validator' ); ?>">
        <h2><?php esc_html_e( 'Verification credits', 'zbounce-email-validator' ); ?></h2>
        <?php \ZbEmailValidator\Settings::render_balance_field(); ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="zb_check_connection">
            <?php wp_nonce_field( 'zb_check_connection' ); ?>
            <?php submit_button( __( 'Check connection and balance', 'zbounce-email-validator' ), 'secondary', 'submit', true, \ZbEmailValidator\Settings::is_api_configured() ? [] : [ 'disabled' => 'disabled' ] ); ?>
        </form>
        <?php
        $zbounce_email_validator_connection = get_transient( 'zb_connection_' . get_current_user_id() );
        if ( is_array( $zbounce_email_validator_connection ) ) {
            echo '<p role="status">' . esc_html( $zbounce_email_validator_connection['message'] ) . '</p>';
            delete_transient( 'zb_connection_' . get_current_user_id() );
        }
        ?>
        <?php if ( \ZbEmailValidator\Settings::is_api_configured() && ! \ZbEmailValidator\Settings::is_verification_enabled() ) : ?>
            <p><?php esc_html_e( 'Automatic form verification is off. The balance and manual tester remain available.', 'zbounce-email-validator' ); ?></p>
        <?php endif; ?>
        <h2><?php esc_html_e( 'Check an email address', 'zbounce-email-validator' ); ?></h2>
        <?php // The widget escapes every field and is restricted to administrators.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo \ZbEmailValidator\Validator::render_validator( [ 'settings_preview' => true ] ); ?>
        <?php if ( ! \ZbEmailValidator\Settings::is_api_configured() ) : ?>
            <p><?php esc_html_e( 'Accept the agreement and save your API key to use the tester.', 'zbounce-email-validator' ); ?>
</p>
        <?php endif; ?>
    </section>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'zb_email_validator_settings' );
        do_settings_sections( 'zbounce-email-validator' );
        submit_button( __( 'Save Settings', 'zbounce-email-validator' ) );
        ?>
    </form>
    <div class="zb-settings-info">
        <h2><?php esc_html_e( 'External service and privacy', 'zbounce-email-validator' ); ?></h2>
        <p><?php esc_html_e( 'During a manual or enabled automatic check, the plugin sends the email address being checked and your API key to https://api.zbounce.net/v1/fast-verify over HTTPS. Checking the connection sends your key to /v1/me without an email address. ZBounce also receives the network address of your WordPress server. Other form fields, passwords, names and payment details are not included by this plugin.', 'zbounce-email-validator' ); ?></p>
        <p><a href="<?php echo esc_url( 'https://zbounce.net/privacy.html' ); ?>"><?php esc_html_e( 'Privacy policy', 'zbounce-email-validator' ); ?></a> · <a href="<?php echo esc_url( 'https://zbounce.net/terms.html' ); ?>"><?php esc_html_e( 'Terms of service', 'zbounce-email-validator' ); ?></a></p>
        <p><?php esc_html_e( 'Verification results, including email addresses, are cached in your WordPress database. Expired results are ignored and removed on later writes. Include this external processing in your own site privacy information.', 'zbounce-email-validator' ); ?></p>
        <h2><?php esc_html_e( 'Using the plugin', 'zbounce-email-validator' ); ?></h2>
        <p><?php esc_html_e( 'Select integrations and accepted results above. The API unavailable rule controls whether a service outage allows or rejects a submission.', 'zbounce-email-validator' ); ?></p>
        <p><?php esc_html_e( 'The standalone test shortcode is available only to logged-in administrators:', 'zbounce-email-validator' ); ?> <code>[zb_email_validator]</code></p>
    </div>
</div>
