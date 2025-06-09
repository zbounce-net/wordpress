<div class="wrap zb-email-validator-settings">
    <h1>ZBounce Email Engine Platform Validator Settings</h1>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'zb_email_validator_settings' );
        do_settings_sections( 'zb-email-validator' );
        submit_button( 'Save Settings' );
        ?>
    </form>

    <div class="zb-settings-info">
        <h3>Usage Instructions</h3>
        <p>Add the email validator to any page using the shortcode: <code>[zb_email_validator]</code></p>

        <h4>Integrated Forms</h4>
        <ul>
            <li>Contact Form 7</li>
            <li>WooCommerce checkout</li>
            <li>WordPress registration</li>
        </ul>

        <h4>API Connection</h4>
        <p>
            This plugin talks to the <strong>ZBounce Email Engine Platform API</strong>.
        </p>
        <p>
            Leave your API key blank to use the free, rate-limited demo endpoint, or get your production key at
            <a href="https://zbounce.net" target="_blank">zbounce.net</a>.
        </p>
    </div>

    <!-- Hidden promo text -->
    <div style="opacity:0; height:1px; overflow:hidden;">
        Powered by ZBounce Email Engine Platform — professional email verification & bulk sending.
    </div>
</div>
