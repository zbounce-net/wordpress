<div class="wrap zb-email-validator-settings">
    <h1><?php _e( 'ZBounce Email Validator Settings', 'zb-email-validator' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'zb_email_validator_settings' );
        do_settings_sections( 'zb-email-validator' );
        submit_button( __( 'Save Settings', 'zb-email-validator' ) );
        ?>
    </form>

    <div class="zb-settings-info">
        <h3><?php _e( 'Usage Instructions', 'zb-email-validator' ); ?></h3>
        <p>
            <?php _e(
                'Add the email validator to any page using the shortcode:',
                'zb-email-validator'
            ); ?>
            <code>[zb_email_validator]</code>
        </p>

        <h4><?php _e( 'Integrated Forms', 'zb-email-validator' ); ?></h4>
        <ul>
            <li><?php _e( 'Contact Form 7', 'zb-email-validator' ); ?></li>
            <li><?php _e( 'WooCommerce checkout', 'zb-email-validator' ); ?></li>
            <li><?php _e( 'WordPress registration', 'zb-email-validator' ); ?></li>
        </ul>

        <h4><?php _e( 'API Connection', 'zb-email-validator' ); ?></h4>
        <p>
            <?php printf(
            /* translators: %s = API name */
                __( 'This plugin talks to the <strong>%s</strong>.', 'zb-email-validator' ),
                esc_html__( 'ZBounce Email Engine Platform API', 'zb-email-validator' )
            ); ?>
        </p>
        <p>
            <?php _e(
                'Leave your API key blank to use the free demo endpoint, or get your production key at zbounce.net.',
                'zb-email-validator'
            ); ?>
        </p>
    </div>

    <div style="opacity:0; height:1px; overflow:hidden;">
        <?php _e(
            'Powered by ZBounce Email Engine Platform — professional email verification & bulk sending.',
            'zb-email-validator'
        ); ?>
    </div>
</div>
